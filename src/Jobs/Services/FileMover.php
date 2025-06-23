<?php

namespace christopheraseidl\ModelFiler\Jobs\Services;

use christopheraseidl\ModelFiler\Jobs\Contracts\CircuitBreaker;
use christopheraseidl\ModelFiler\Jobs\Contracts\FileMover as FileMoverContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Moves files with retry logic and circuit breaker protection for rollback capability.
 */
class FileMover extends FileOperator implements FileMoverContract
{
    /**
     * Track moved files for rollback (old path => new path).
     */
    public function __construct(
        protected CircuitBreaker $breaker,
        protected array $movedFiles = []
    ) {}

    /**
     * Move file with retry logic and automatic rollback on failure.
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
            $results = $this->processMove($disk, $oldPath, $newPath, $maxAttempts);
        } catch (\Exception $e) {
            $this->handleCaughtAttemptMoveException($disk, $oldPath, $newPath, $maxAttempts, $e);
        }

        return $results ?? '';
    }

    /**
     * Rollback previously moved files.
     */
    public function attemptUndoMove(string $disk, int $maxAttempts = 3, bool $throwOnFailure = true): array
    {
        $this->validateMaxAttempts($maxAttempts);

        $results = $this->processAllUndoOperations($disk, $maxAttempts);

        if (empty($results['failures'])) {
            $this->handleAttemptUndoSuccess();
        } else {
            $this->handleAttemptUndoFailure($disk, $results, $throwOnFailure);
        }

        return $results;
    }

    /**
     * Process single file move with retry logic.
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

                $this->handleProcessMoveCaughtException($disk, $attempts, $maxAttempts, $e->getMessage());
            }
        }

        // If we get here, all attempts failed
        $this->handleProcessMoveFailure($attempts, $maxAttempts, $lastException);

        return '';
    }

    /**
     * Execute file move operation using copy and delete for atomicity.
     */
    public function performMove(string $disk, string $oldPath, string $newPath): string
    {
        $uniquePath = $this->generateUniqueFileName($disk, $newPath);

        $this->copyFile($disk, $oldPath, $uniquePath);
        $this->validateCopiedFile($disk, $uniquePath);
        $this->deleteFile($disk, $oldPath);

        $commit = $this->commitMovedFile($oldPath, $uniquePath);

        $this->getBreaker()->recordSuccess();

        return $commit;
    }

    /**
     * Process all pending undo operations.
     */
    public function processAllUndoOperations(string $disk, int $maxAttempts): array
    {
        if (! $this->getBreaker()->canAttempt()) {
            $this->handleCircuitBreakerBlock($disk);

            return ['failures' => $this->getMovedFiles(), 'successes' => []];
        }

        $failures = [];
        $successes = [];

        foreach ($this->getMovedFiles() as $oldPath => $newPath) {
            $result = $this->processSingleUndo($disk, $oldPath, $newPath, $maxAttempts);

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
    public function processSingleUndo(string $disk, string $oldPath, string $newPath, int $maxAttempts): bool
    {
        $attempts = 0;

        while ($attempts < $maxAttempts && $this->getBreaker()->canAttempt()) {
            try {
                $this->performUndo($disk, $oldPath, $newPath);
                $this->getBreaker()->recordSuccess();

                return true;
            } catch (\Exception $e) {
                $attempts++;

                $this->handleCaughtProcessSingleUndoException(
                    $disk,
                    $oldPath,
                    $newPath,
                    $attempts,
                    $maxAttempts,
                    $e
                );
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
        if (! $this->fileExists($disk, $newPath)) {
            return; // Nothing to undo
        }

        // Restore original if it doesn't exist
        if (! $this->fileExists($disk, $oldPath)) {
            $this->copyFile($disk, $newPath, $oldPath);
        }

        // Verify restoration before deleting
        if ($this->fileExists($disk, $oldPath)) {
            $this->deleteFile($disk, $newPath);
        } else {
            throw new \Exception('Failed to restore file to original location.');
        }
    }

    /**
     * Execute Laravel Storage method with array of paths.
     */
    public function doStorage(string $disk, string $method, array $paths): void
    {
        $pathsString = implode(' to ', $paths);
        $exceptionMessage = "Failed to {$method} file: {$pathsString}";

        try {
            $result = Storage::disk($disk)->$method(...$paths);

            $this->validateStorageResult($result, $exceptionMessage);
        } catch (\Exception $e) {
            $this->handleCaughtStorageException($e, $exceptionMessage);
        }
    }

    /**
     * Copy file from source to destination.
     */
    public function copyFile(string $disk, string $oldPath, string $newPath): void
    {
        $this->doStorage($disk, 'copy', [$oldPath, $newPath]);
    }

    /**
     * Delete a single file.
     */
    public function deleteFile(string $disk, string $path): void
    {
        $this->doStorage($disk, 'delete', [$path]);
    }

    /**
     * Check file existence excluding zero-byte files.
     */
    public function fileExists(string $disk, string $path): bool
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

        while ($this->fileExists($disk, $path)) {
            $path = $info['dirname'].'/'.$info['filename'].'_'.$counter.'.'.$info['extension'];
            $counter++;
        }

        return $path;
    }

    /**
     * Build destination path from directory and source filename.
     */
    public function buildNewPath(string $newDir, string $oldPath): string
    {
        return "{$newDir}/".pathinfo($oldPath, PATHINFO_BASENAME);
    }

    /**
     * Validate storage operation result and handle failure.
     */
    public function validateStorageResult(bool $successful, string $exceptionMessage): void
    {
        if (! $successful) {
            $this->getBreaker()->recordFailure();

            throw new \Exception($exceptionMessage);
        }
    }

    /**
     * Verify copied file exists at destination.
     */
    public function validateCopiedFile(string $disk, string $newPath): void
    {
        if (! $this->fileExists($disk, $newPath)) {
            $this->getBreaker()->recordFailure();
            throw new \Exception('Copy succeeded but file not found at destination.');
        }
    }

    /**
     * Handle exceptions caught in the attemptMove() method.
     */
    public function handleCaughtAttemptMoveException(string $disk, string $oldPath, string $newDir, int $maxAttempts, \Throwable $exception): void
    {
        Log::error("Failed to move file after {$maxAttempts} attempts.", [
            'disk' => $disk,
            'old_path' => $oldPath,
            'new_dir' => $newDir,
            'max_attempts' => $maxAttempts,
            'last_error' => $exception->getMessage(),
        ]);

        throw new \Exception("Failed to move file after {$maxAttempts} attempts.", 0, $exception);
    }

    /**
     * Handle caught storage operation exceptions.
     */
    public function handleCaughtStorageException(\Throwable $exception, string $exceptionMessage): void
    {
        // Re-throw if it's already our custom exception
        if ($exception->getMessage() === $exceptionMessage) {
            throw $exception;
        }

        $this->getBreaker()->recordFailure();

        // Wrap other exceptions
        throw new \Exception("{$exceptionMessage}. ".$exception->getMessage(), 0, $exception);
    }

    /**
     * Handle failure during move operation with optional rollback.
     */
    public function handleProcessMoveCaughtException(string $disk, int $attempts, int $maxAttempts, string $exceptionMessage): void
    {
        Log::warning('Move attempt failed.', [
            'attempt' => $attempts,
            'error' => $exceptionMessage,
        ]);

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

    public function handleProcessMoveFailure(int $attempts, int $maxAttempts, ?\Throwable $exception = null): void
    {
        if ($this->getBreaker()->maxAttemptsReached($attempts, $maxAttempts)) {
            $this->getBreaker()->recordFailure();
        }

        throw $exception ?? new \Exception('Move failed without exception');
    }

    /**
     * Handle exceptions caught during processSingleUndo().
     */
    public function handleCaughtProcessSingleUndoException(
        string $disk,
        string $oldPath,
        string $newPath,
        int $attempts,
        int $maxAttempts,
        \Throwable $exception
    ) {
        Log::warning('Undo attempt failed.', [
            'disk' => $disk,
            'attempt' => $attempts,
            [
                'old_path' => $oldPath,
                'new_path' => $newPath,
            ],
            'error' => $exception->getMessage(),
        ]);

        if ($attempts < $maxAttempts) {
            $this->waitBeforeRetry();
        }
    }

    /**
     * Handle circuit breaker blocking undo operations.
     */
    public function handleCircuitBreakerBlock(string $disk): void
    {
        Log::warning('File move undo blocked by circuit breaker', [
            'disk' => $disk,
            'pending_undos' => count($this->getMovedFiles()),
        ]);
    }

    /**
     * Handle successful completion of all undo operations.
     */
    public function handleAttemptUndoSuccess(): void
    {
        $this->clearMovedFiles();
    }

    /**
     * Handle failure during undo operations.
     */
    public function handleAttemptUndoFailure(string $disk, array $results, bool $throwOnFailure = true): void
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
        $movedFiles = $this->getMovedFiles();
        unset($movedFiles[$oldPath]);
        $this->movedFiles = $movedFiles;
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
