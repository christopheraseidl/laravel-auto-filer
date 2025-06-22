<?php

namespace christopheraseidl\ModelFiler\Jobs\Services;

use christopheraseidl\ModelFiler\Jobs\Contracts\CircuitBreaker;
use christopheraseidl\ModelFiler\Jobs\Contracts\FileMover as FileMoverContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Handles file move operations with retry logic and circuit breaker protection.
 */
class FileMover extends FileOperator implements FileMoverContract
{
    /**
     * The $movedFiles array is used to track moved files for rollback (old path => new path).
     */
    public function __construct(
        protected CircuitBreaker $breaker,
        protected array $movedFiles = []
    ) {}

    /**
     * Attempt file move with retry logic and automatic rollback on failure.
     */
    public function attemptMove(string $disk, string $oldPath, string $newDir, int $maxAttempts = 3): string
    {
        $this->validateMaxAttempts($maxAttempts);
        $this->checkCircuitBreaker('attempt move file', $disk, [
            'old_path' => $oldPath,
            'new_dir' => $newDir,
        ]);

        $newPath = $this->buildNewPath($newDir, $oldPath);

        try {
            return $this->processMove($disk, $oldPath, $newPath, $maxAttempts);
        } catch (\Exception $e) {
            Log::error("Failed to move file after {$maxAttempts} attempts.", [
                'disk' => $disk,
                'old_path' => $oldPath,
                'new_dir' => $newDir,
                'max_attempts' => $maxAttempts,
                'last_error' => $e->getMessage(),
            ]);

            throw new \Exception("Failed to move file after {$maxAttempts} attempts.", 0, $e);
        }
    }

    /**
     * Attempt rollback of previously moved files.
     */
    public function attemptUndoMove(string $disk, int $maxAttempts = 3, bool $throwOnFailure = true): array
    {
        $this->validateMaxAttempts($maxAttempts);

        $results = $this->processUndoOperations($disk, $maxAttempts);

        if (empty($results['failures'])) {
            $this->handleSuccessfulUndo();
        } else {
            $this->handleUndoFailure($disk, $results, true);
        }

        return $results['successes'];
    }

    /**
     * Process a single file move attempt.
     */
    public function processMove(string $disk, string $oldPath, string $newPath, int $maxAttempts): string
    {
        $lastException = null;
        $attempts = 0;

        while ($attempts < $maxAttempts && $this->getBreaker()->canAttempt()) {
            try {
                return $this->performMove($disk, $oldPath, $newPath);
            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;

                Log::warning('Move attempt failed.', [
                    'attempt' => $attempts,
                    'error' => $e->getMessage(),
                ]);

                $this->handleMoveFailure($disk, $attempts, $maxAttempts);
            }
        }

        if ($this->getBreaker()->maxAttemptsReached($attempts, $maxAttempts)) {
            $this->getBreaker()->recordFailure();
        }

        // If we get here, all attempts failed
        throw $lastException ?? new \Exception('Move failed without exception');
    }

    /**
     * Execute file move operation using copy and delete for atomicity.
     */
    public function performMove(string $disk, string $oldPath, string $newPath): string
    {
        $uniquePath = $this->generateUniqueFileName($disk, $newPath);

        $this->copyFile($disk, $oldPath, $uniquePath);
        $this->validateCopiedFile($disk, $uniquePath);
        Storage::disk($disk)->delete($oldPath);
        $commit = $this->commitMovedFile($oldPath, $uniquePath);
        $this->getBreaker()->recordSuccess();

        return $commit;
    }

    /**
     * Copy file from source to destination.
     */
    public function copyFile(string $disk, string $oldPath, string $newPath): void
    {
        if (! Storage::disk($disk)->copy($oldPath, $newPath)) {
            $this->getBreaker()->recordFailure();
            throw new \Exception('Failed to copy file.');
        }
    }

    /**
     * Verify copied file exists at destination.
     */
    public function validateCopiedFile(string $disk, string $newPath): void
    {
        if (! $this->exists($disk, $newPath)) {
            $this->getBreaker()->recordFailure();
            throw new \Exception('Copy succeeded but file not found at destination.');
        }
    }

    /**
     * Process all pending undo operations.
     */
    public function processUndoOperations(string $disk, int $maxAttempts): array
    {
        if (! $this->getBreaker()->canAttempt()) {
            Log::warning('File move undo blocked by circuit breaker', [
                'disk' => $disk,
                'pending_undos' => count($this->getMovedFiles()),
            ]);

            return ['failures' => $this->getMovedFiles(), 'successes' => []];
        }

        $failures = [];
        $successes = [];

        foreach ($this->getMovedFiles() as $oldPath => $newPath) {
            $result = $this->attemptSingleUndo($disk, $oldPath, $newPath, $maxAttempts);

            if ($result) {
                $successes[$oldPath] = $newPath;
            } else {
                $failures[$oldPath] = $newPath;
            }
        }

        return ['failures' => $failures, 'successes' => $successes];
    }

    /**
     * Attempt rollback of single file move.
     */
    public function attemptSingleUndo(string $disk, string $oldPath, string $newPath, int $maxAttempts): bool
    {
        $attempts = 0;

        while ($attempts < $maxAttempts && $this->getBreaker()->canAttempt()) {
            try {
                $this->performUndo($disk, $oldPath, $newPath);
                $this->getBreaker()->recordSuccess();

                return true;
            } catch (\Exception $e) {
                $attempts++;

                Log::warning('Undo attempt failed.', [
                    'disk' => $disk,
                    'attempt' => $attempts,
                    [
                        'old_path' => $oldPath,
                        'new_path' => $newPath,
                    ],
                    'error' => $e->getMessage(),
                ]);

                if ($attempts < $maxAttempts) {
                    $this->waitBeforeRetry();
                }
            }
        }

        if ($this->getBreaker()->maxAttemptsReached($attempts, $maxAttempts)) {
            $this->getBreaker()->recordFailure();
        }

        return false;
    }

    /**
     * Execute single file rollback operation.
     */
    public function performUndo(string $disk, string $oldPath, string $newPath): void
    {
        if (! $this->exists($disk, $newPath)) {
            return; // Nothing to undo
        }

        // Restore original if it doesn't exist
        if (! $this->exists($disk, $oldPath)) {
            Storage::disk($disk)->copy($newPath, $oldPath);
        }

        // Verify restoration before deleting
        if ($this->exists($disk, $oldPath)) {
            Storage::disk($disk)->delete($newPath);
        } else {
            throw new \Exception('Failed to restore file to original location.');
        }
    }

    /**
     * Handle failure during move operation with optional rollback.
     */
    public function handleMoveFailure(string $disk, int $attempts, int $maxAttempts): void
    {
        if ($this->getBreaker()->maxAttemptsReached($attempts, $maxAttempts)) {
            if (! empty($this->getMovedFiles())) {
                try {
                    $this->attemptUndoMove($disk, $maxAttempts);
                } catch (\Exception $e) {
                    Log::error('Unexpected exception during undo after move failure.', [
                        'disk' => $disk,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } elseif ($this->getBreaker()->canAttempt()) {
            $this->waitBeforeRetry();
        }
    }

    /**
     * Handle successful completion of all undo operations.
     */
    public function handleSuccessfulUndo(): void
    {
        $this->clearMovedFiles();
    }

    /**
     * Handle failure during undo operations.
     */
    public function handleUndoFailure(string $disk, array $results, bool $throwOnFailure = true): void
    {
        $this->getBreaker()->recordFailure();

        Log::error('File move undo failure.', [
            'disk' => $disk,
            'failed' => $results['failures'],
            'succeeded' => $results['successes'],
        ]);

        $this->uncommitSuccessfulUndos($results['successes']);

        if ($throwOnFailure) {
            throw new \Exception(sprintf(
                'Failed to undo %d file move(s): %s',
                count($results['failures']),
                json_encode($results['failures'], JSON_PRETTY_PRINT)
            ));
        }
    }

    /**
     * Remove successfully undone files from tracking.
     */
    public function uncommitSuccessfulUndos(array $successes): void
    {
        foreach ($successes as $oldPath => $newPath) {
            $this->uncommitMovedFile($oldPath);
        }
    }

    /**
     * Build destination path from directory and source filename.
     */
    public function buildNewPath(string $newDir, string $oldPath): string
    {
        return "{$newDir}/".pathinfo($oldPath, PATHINFO_BASENAME);
    }

    /**
     * Check file existence excluding zero-byte files.
     */
    public function exists(string $disk, string $path): bool
    {
        return Storage::disk($disk)->exists($path) && Storage::disk($disk)->size($path) > 0;
    }

    /**
     * Generate unique filename by appending counter if necessary.
     */
    public function generateUniqueFileName(string $disk, string $path): string
    {
        $info = pathinfo($path);
        $counter = 1;

        while (Storage::disk($disk)->exists($path)) {
            $path = $info['dirname'].'/'.$info['filename'].'_'.$counter.'.'.$info['extension'];
            $counter++;
        }

        return $path;
    }

    /**
     * Record successful file move for potential rollback.
     */
    public function commitMovedFile(string $oldPath, string $newPath): string
    {
        return $this->addToMovedFiles($oldPath, $newPath);
    }

    /**
     * Get all tracked file moves.
     */
    public function addToMovedFiles(string $key, string $value): string
    {
        return $this->movedFiles[$key] = $value;
    }

    /**
     * Remove file from rollback tracking.
     */
    public function uncommitMovedFile(string $oldPath): void
    {
        unset($this->getMovedFiles()[$oldPath]);
    }

    /**
     * Clear all tracked file moves.
     */
    public function clearMovedFiles(): void
    {
        $this->movedFiles = [];
    }

    /**
     * Get all tracked file moves.
     */
    public function getMovedFiles(): array
    {
        return $this->movedFiles;
    }
}
