<?php

namespace christopheraseidl\AutoFiler\Services;

use christopheraseidl\AutoFiler\Contracts\FileMover;
use christopheraseidl\AutoFiler\Contracts\GenerateThumbnail;
use christopheraseidl\AutoFiler\Exceptions\FileMoveException;
use christopheraseidl\AutoFiler\Exceptions\FileRollbackException;
use christopheraseidl\AutoFiler\Exceptions\FileValidationException;
use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Moves files with retry logic and circuit breaker protection for rollback capability.
 */
class FileMoverService extends BaseFileOperator implements FileMover
{
    private array $movedFiles = [];

    public function __construct(
        private readonly CircuitBreakerContract $circuitBreaker,
        private readonly GenerateThumbnail $generateThumbnail
    ) {
        parent::__construct($circuitBreaker);
    }

    /**
     * Move a file from the source path to the indicated destination folder.
     */
    public function move(string $sourcePath, string $destinationPath): string
    {
        $this->checkCircuitBreaker('attempt move file', [
            'source_path' => $sourcePath,
            'destination_folder' => $destinationPath,
        ]);

        return $this->attemptMove($sourcePath, $destinationPath);
    }

    /**
     * Move file with retry logic and automatic rollback on failure.
     */
    protected function attemptMove(string $sourcePath, string $destinationPath): string
    {
        try {
            $results = $this->moveWithRetries($sourcePath, $destinationPath);
        } catch (\Throwable $e) {
            $this->handleMoveFailed($sourcePath, $destinationPath, $e);
        }

        return $results ?? '';
    }

    /**
     * Process single file move with retry logic.
     */
    protected function moveWithRetries(string $sourcePath, string $destinationPath): string
    {
        $lastException = null;
        $attempts = 0;

        while ($attempts < $this->maxAttempts && $this->getBreaker()->canAttempt()) {
            try {
                return $this->executeMove($sourcePath, $destinationPath);
            } catch (\Throwable $e) {
                $lastException = $e;
                $attempts++;

                $this->handleMoveRetryFailed($attempts, $e->getMessage());
            }
        }

        // If we get here, all attempts failed
        $this->handleAllMoveAttemptsFailed($attempts, $lastException);

        return '';
    }

    /**
     * Execute file move operation using copy and delete for atomicity.
     */
    protected function executeMove(string $sourcePath, string $destinationPath): string
    {
        try {
            Storage::disk($this->disk)->copy($sourcePath, $destinationPath);
            $this->validateCopiedFile($destinationPath);

            // Generate thumbnail if enabled and file is an image
            $thumbnailPath = null;
            if ($this->shouldGenerateThumbnail($destinationPath)) {
                $thumbnailPath = $this->generateThumbnailAndCommit($destinationPath);
            }

            Storage::disk($this->disk)->delete($sourcePath);

            $commit = $this->commitMovedFile($sourcePath, $destinationPath);

            $this->getBreaker()->recordSuccess();

            return $commit;
        } catch (\Throwable $e) {
            // Clean up destination file on failure
            if ($this->fileExists($destinationPath)) {
                Storage::disk($this->disk)->delete($destinationPath);
            }

            // Uncommit the now-deleted file
            $this->uncommitMovedFile($sourcePath);

            // TODO: Delete thumbnail, if any
            $this->uncommitMovedFile($sourcePath.'_thumb');
            if (!is_null($thumbnailPath)) {
                if ($this->fileExists($thumbnailPath)) {
                    Storage::disk($this->disk)->delete($thumbnailPath);
                }
            }

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Rollback previously moved files.
     */
    protected function attemptUndoMove(bool $throwOnFailure = true): array
    {
        $results = $this->undoAllMoves();

        if (empty($results['failures'])) {
            $this->handleAllUndosSucceeded();
        } else {
            $this->handleUndosFailed($results, $throwOnFailure);
        }

        return $results;
    }

    /**
     * Process all pending undo operations.
     */
    protected function undoAllMoves(): array
    {
        if (! $this->getBreaker()->canAttempt()) {
            $this->handleCircuitBreakerOpen();

            return ['failures' => $this->getMovedFiles(), 'successes' => []];
        }

        $failures = [];
        $successes = [];

        foreach ($this->getMovedFiles() as $sourcePath => $destinationPath) {
            $result = $this->undoWithRetries($sourcePath, $destinationPath);

            if ($result) {
                $successes[$sourcePath] = $destinationPath;
            } else {
                $failures[$sourcePath] = $destinationPath;
            }
        }

        return ['failures' => $failures, 'successes' => $successes];
    }

    /**
     * Attempt rollback of single file move.
     */
    protected function undoWithRetries(string $sourcePath, string $destinationPath): bool
    {
        $attempts = 0;

        while ($attempts < $this->maxAttempts && $this->getBreaker()->canAttempt()) {
            try {
                $this->executeUndo($sourcePath, $destinationPath);
                $this->getBreaker()->recordSuccess();

                return true;
            } catch (\Throwable $e) {
                $attempts++;

                $this->handleUndoRetryFailed(
                    $sourcePath,
                    $destinationPath,
                    $attempts,
                    $e
                );
            }
        }

        if (! $this->getBreaker()->canAttempt()) {
            $this->getBreaker()->recordFailure();
        }

        return false;
    }

    /**
     * Execute single file rollback operation.
     */
    protected function executeUndo(string $sourcePath, string $destinationPath): void
    {
        if (! $this->fileExists($destinationPath)) {
            return; // Nothing to undo
        }

        // If it's a thumbnail, just delete it and return.
        if (str_ends_with($sourcePath, '_thumb')) {
            // Just delete the thumbnail from destination; don't "restore" it to source.
            Storage::disk($this->disk)->delete($destinationPath);

            return;
        }

        // Restore original if it doesn't exist
        if (! $this->fileExists($sourcePath)) {
            Storage::disk($this->disk)->copy($destinationPath, $sourcePath);
        }

        // Verify restoration before deleting
        if ($this->fileExists($sourcePath)) {
            Storage::disk($this->disk)->delete($destinationPath);
        } else {
            throw new FileRollbackException('Failed to restore file to original location.');
        }
    }

    /**
     * Check if thumbnail should be generated for file.
     */
    protected function shouldGenerateThumbnail(string $path): bool
    {
        if (! config('auto-filer.thumbnails.enabled')) {
            return false;
        }

        $mimeType = Storage::disk($this->disk)->mimeType($path);

        return str_starts_with($mimeType, 'image/');
    }

    /**
     * Generate thumbnail for image file.
     */
    protected function generateThumbnailAndCommit(string $imagePath): ?string
    {
        $result = $this->generateThumbnail($imagePath);
            
        if ($result['success']) {
            // Track thumbnail for potential rollback
            $this->commitMovedFile($imagePath.'_thumb', $result['path']);

            return $result['path'];
        }

        return null;
    }

    /**
     * Verify copied file exists at destination.
     */
    protected function validateCopiedFile(string $destinationPath): void
    {
        if (! $this->fileExists($destinationPath)) {
            $this->getBreaker()->recordFailure();
            throw new FileValidationException('Copy succeeded but file not found at destination.');
        }
    }

    /**
     * Check file existence excluding zero-byte files.
     */
    protected function fileExists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path) && Storage::disk($this->disk)->size($path) > 0;
    }

    /**
     * Handle exceptions caught in the attemptMove() method.
     */
    protected function handleMoveFailed(string $sourcePath, string $destinationDir, \Throwable $exception): void
    {
        Log::error("Failed to move file after {$this->maxAttempts} attempts.", [
            'disk' => $this->disk,
            'source_path' => $sourcePath,
            'destination_dir' => $destinationDir,
            'max_attempts' => $this->maxAttempts,
            'last_error' => $exception->getMessage(),
        ]);

        throw new FileMoveException("Failed to move file after {$this->maxAttempts} attempts.", 0, $exception);
    }

    /**
     * Handle failure during move operation with optional rollback.
     */
    protected function handleMoveRetryFailed(int $attempts, string $exceptionMessage): void
    {
        Log::warning('Move attempt failed.', [
            'attempt' => $attempts,
            'error' => $exceptionMessage,
        ]);

        if ($this->getBreaker()->canAttempt()) {
            if (! empty($this->getMovedFiles())) {
                try {
                    $this->attemptUndoMove();
                } catch (\Throwable $e) {
                    Log::error('Unexpected exception during undo after move failure.', [
                        'disk' => $this->disk,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } else {
            $this->waitBeforeRetry();
        }
    }

    protected function handleAllMoveAttemptsFailed(int $attempts, ?\Throwable $exception = null): void
    {
        if (! $this->getBreaker()->canAttempt()) {
            $this->getBreaker()->recordFailure();
        }

        throw $exception ?? new FileMoveException('Move failed without exception');
    }

    /**
     * Handle exceptions caught during processSingleUndo().
     */
    protected function handleUndoRetryFailed(
        string $sourcePath,
        string $destinationPath,
        int $attempts,
        \Throwable $exception
    ) {
        Log::warning('Undo attempt failed.', [
            'disk' => $this->disk,
            'attempt' => $attempts,
            'source_path' => $sourcePath,
            'destination_path' => $destinationPath,
            'error' => $exception->getMessage(),
        ]);

        if ($attempts < $this->maxAttempts) {
            $this->waitBeforeRetry();
        }
    }

    /**
     * Handle circuit breaker blocking undo operations.
     */
    protected function handleCircuitBreakerOpen(): void
    {
        Log::warning('File move undo blocked by circuit breaker', [
            'disk' => $this->disk,
            'pending_undos' => count($this->getMovedFiles()),
        ]);
    }

    /**
     * Handle successful completion of all undo operations.
     */
    protected function handleAllUndosSucceeded(): void
    {
        $this->clearMovedFiles();
    }

    /**
     * Handle failure during undo operations.
     */
    protected function handleUndosFailed(array $results, bool $throwOnFailure = true): void
    {
        $this->getBreaker()->recordFailure();

        Log::error('File move undo failure.', [
            'disk' => $this->disk,
            'failed' => $results['failures'],
            'succeeded' => $results['successes'],
        ]);

        $this->uncommitSuccessfulUndos($results['successes']);

        if ($throwOnFailure) {
            throw new FileRollbackException(sprintf(
                'Failed to undo %d file move(s): %s',
                count($results['failures']),
                json_encode($results['failures'], JSON_PRETTY_PRINT)
            ));
        }
    }

    /**
     * Record successful file move for potential rollback.
     */
    private function commitMovedFile(string $sourcePath, string $destinationPath): string
    {
        return $this->addToMovedFiles($sourcePath, $destinationPath);
    }

    /**
     * Get all tracked file moves.
     */
    private function addToMovedFiles(string $key, string $value): string
    {
        return $this->movedFiles[$key] = $value;
    }

    /**
     * Remove file from rollback tracking.
     */
    private function uncommitMovedFile(string $sourcePath): void
    {
        unset($this->movedFiles[$sourcePath]);
    }

    /**
     * Remove successfully undone files from tracking.
     */
    private function uncommitSuccessfulUndos(array $successes): void
    {
        foreach ($successes as $sourcePath => $destinationPath) {
            $this->uncommitMovedFile($sourcePath);
        }
    }

    /**
     * Clear all tracked file moves.
     */
    private function clearMovedFiles(): void
    {
        $this->movedFiles = [];
    }

    /**
     * Get all tracked file moves.
     */
    private function getMovedFiles(): array
    {
        return $this->movedFiles;
    }
}
