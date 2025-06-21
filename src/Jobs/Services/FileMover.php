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
     * Attempt to move a file with retry logic and automatic rollback.
     */
    public function attemptMove(string $disk, string $oldPath, string $newDir, int $maxAttempts = 3): string
    {
        $this->validateMaxAttempts($maxAttempts);
        $this->checkCircuitBreaker('attempt move file', $disk, [
            'old_path' => $oldPath,
            'new_dir' => $newDir,
        ]);

        $newPath = $this->buildNewPath($newDir, $oldPath);
        $lastException = null;
        $attempts = 0;

        while ($attempts < $maxAttempts && $this->breaker->canAttempt()) {
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

        if ($this->breaker->maxAttemptsReached($attempts, $maxAttempts)) {
            $this->breaker->recordFailure();
        }

        Log::error("Failed to move file after {$maxAttempts} attempts.", [
            'disk' => $disk,
            'old_path' => $oldPath,
            'new_dir' => $newDir,
            'max_attempts' => $maxAttempts,
            'last_error' => $lastException?->getMessage(),
        ]);

        throw new \Exception("Failed to move file after {$maxAttempts} attempts.");
    }

    /**
     * Attempt to undo previously moved files.
     */
    public function attemptUndoMove(string $disk, int $maxAttempts = 3, bool $throwOnFailure = true): array
    {
        $this->validateMaxAttempts($maxAttempts);

        $results = $this->processUndoOperations($disk, $maxAttempts);

        if (empty($results['failures'])) {
            $this->handleSuccessfulUndo();
        } else {
            $this->handleUndoFailure($disk, $results, $throwOnFailure);
        }

        return $results['successes'];
    }

    /**
     * Perform the actual file move operation using copy+delete for atomicity.
     */
    protected function performMove(string $disk, string $oldPath, string $newPath): string
    {
        $uniquePath = $this->generateUniqueFileName($disk, $newPath);

        $this->copyFile($disk, $oldPath, $uniquePath);
        $this->validateCopiedFile($disk, $uniquePath);
        Storage::disk($disk)->delete($oldPath);
        $commit = $this->commitMovedFile($oldPath, $uniquePath);
        $this->breaker->recordSuccess();

        return $commit;
    }

    protected function copyFile(string $disk, string $oldPath, string $newPath): void
    {
        if (! Storage::disk($disk)->copy($oldPath, $newPath)) {
            $this->breaker->recordFailure();
            throw new \Exception('Failed to copy file.');
        }
    }

    protected function validateCopiedFile(string $disk, string $newPath): void
    {
        if (! $this->exists($disk, $newPath)) {
            $this->breaker->recordFailure();
            throw new \Exception('Copy succeeded but file not found at destination.');
        }
    }

    protected function processUndoOperations(string $disk, int $maxAttempts): array
    {
        if (! $this->breaker->canAttempt()) {
            $this->logWarning('File move undo blocked by circuit breaker', [
                'disk' => $disk,
                'pending_undos' => count($this->movedFiles),
            ]);

            return ['failures' => $this->movedFiles, 'successes' => []];
        }

        $failures = [];
        $successes = [];

        foreach ($this->movedFiles as $oldPath => $newPath) {
            $result = $this->attemptSingleUndo($disk, $oldPath, $newPath, $maxAttempts);

            if ($result) {
                $successes[$oldPath] = $newPath;
            } else {
                $failures[$oldPath] = $newPath;
            }
        }

        return ['failures' => $failures, 'successes' => $successes];
    }

    protected function attemptSingleUndo(string $disk, string $oldPath, string $newPath, int $maxAttempts): bool
    {
        $attempts = 0;

        while ($attempts < $maxAttempts && $this->breaker->canAttempt()) {
            try {
                $this->performUndo($disk, $oldPath, $newPath);
                $this->breaker->recordSuccess();

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

        if ($this->breaker->maxAttemptsReached($attempts, $maxAttempts)) {
            $this->breaker->recordFailure();
        }

        return false;
    }

    protected function performUndo(string $disk, string $oldPath, string $newPath): void
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

    protected function handleMoveFailure(string $disk, int $attempts, int $maxAttempts): void
    {
        if ($this->breaker->maxAttemptsReached($attempts, $maxAttempts)) {
            if (! empty($this->movedFiles)) {
                try {
                    $this->attemptUndoMove($disk, $maxAttempts, false);
                } catch (\Exception $e) {
                    Log::error('Unexpected exception during undo after move failure.', [
                        'disk' => $disk,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } elseif ($this->breaker->canAttempt()) {
            $this->waitBeforeRetry();
        }
    }

    protected function handleSuccessfulUndo(): void
    {
        $this->clearMovedFiles();
    }

    protected function handleUndoFailure(string $disk, array $results, bool $throwOnFailure = true): void
    {
        $this->breaker->recordFailure();

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

    protected function uncommitSuccessfulUndos(array $successes): void
    {
        foreach ($successes as $oldPath => $newPath) {
            $this->uncommitMovedFile($oldPath);
        }
    }

    protected function buildNewPath(string $newDir, string $oldPath): string
    {
        return "{$newDir}/".pathinfo($oldPath, PATHINFO_BASENAME);
    }

    /**
     * Checks for file existence and size, treating zero-byte files as non-existent.
     */
    protected function exists(string $disk, string $path): bool
    {
        return Storage::disk($disk)->exists($path) && Storage::disk($disk)->size($path) > 0;
    }

    /**
     * Append a number to the end of the file name if necessary.
     */
    protected function generateUniqueFileName(string $disk, string $path): string
    {
        $info = pathinfo($path);
        $counter = 1;

        while (Storage::disk($disk)->exists($path)) {
            $path = $info['dirname'].'/'.$info['filename'].'_'.$counter.'.'.$info['extension'];
            $counter++;
        }

        return $path;
    }

    private function commitMovedFile(string $oldPath, string $newPath): string
    {
        return $this->movedFiles[$oldPath] = $newPath;
    }

    private function uncommitMovedFile(string $oldPath): void
    {
        unset($this->movedFiles[$oldPath]);
    }

    private function clearMovedFiles(): void
    {
        $this->movedFiles = [];
    }
}
