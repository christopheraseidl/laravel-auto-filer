<?php

namespace christopheraseidl\ModelFiler\Services;

use christopheraseidl\ModelFiler\Contracts\FileMover;
use christopheraseidl\ModelFiler\Exceptions\FileMoveException;
use christopheraseidl\ModelFiler\Exceptions\FileRollbackException;
use christopheraseidl\ModelFiler\Exceptions\FileValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Moves files with retry logic and circuit breaker protection for rollback capability.
 */
class FileMoverService extends BaseFileOperator implements FileMover
{
    private array $movedFiles = [];
    
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
    private function attemptMove(string $sourcePath, string $destinationPath): string
    {
        try {
            $results = $this->processMove($sourcePath, $destinationPath);
        } catch (\Throwable $e) {
            $this->handleAttemptMoveException($sourcePath, $destinationPath, $e);
        }

        return $results ?? '';
    }

    /**
     * Process single file move with retry logic.
     */
    private function processMove(string $sourcePath, string $destinationPath): string
    {
        $lastException = null;
        $attempts = 0;

        while ($attempts < $this->maxAttempts && $this->getBreaker()->canAttempt()) {
            try {
                return $this->performMove($sourcePath, $destinationPath);
            } catch (\Throwable $e) {
                $lastException = $e;
                $attempts++;

                $this->handleProcessMoveException($attempts, $e->getMessage());
            }
        }

        // If we get here, all attempts failed
        $this->handleProcessMoveFailure($attempts, $lastException);

        return '';
    }

    /**
     * Execute file move operation using copy and delete for atomicity.
     */
    private function performMove(string $sourcePath, string $destinationPath): string
    {
        Storage::disk($this->disk)->copy($sourcePath, $destinationPath);
        $this->validateCopiedFile($destinationPath);

        // Generate thumbnail if enabled and file is an image
        if ($this->shouldGenerateThumbnail($destinationPath)) {
            $this->generateThumbnail($destinationPath);
        }
        
        Storage::disk($this->disk)->delete($sourcePath);

        $commit = $this->commitMovedFile($sourcePath, $destinationPath);

        $this->getBreaker()->recordSuccess();

        return $commit;
    }

    /**
     * Check if thumbnail should be generated for file.
     */
    private function shouldGenerateThumbnail(string $path): bool
    {
        if (!config('model-filer.thumbnails.enabled')) {
            return false;
        }
        
        $mimeType = Storage::disk($this->disk)->mimeType($path);
        return str_starts_with($mimeType, 'image/');
    }

    /**
     * Generate thumbnail for image file.
     */
    private function generateThumbnail(string $imagePath): void
    {
        try {
            $image = Image::read(Storage::disk($this->disk)->get($imagePath));
            
            $width = config('model-filer.thumbnails.width');
            $height = config('model-filer.thumbnails.height');

            // Resize maintaining aspect ratio
            $image->scale($width, $height);

            // Build thumbnail path
            $info = pathinfo($imagePath);
            $thumbPath = $info['dirname'] . '/' . 
                        $info['filename'] . 
                        config('model-filer.thumbnails.suffix') . 
                        '.' . $info['extension'];

            // Save thumbnail
            Storage::disk($this->disk)->put(
                $thumbPath,
                $image->encodeByExtension($info['extension'], quality: config('model-filer.thumbnails.quality'))
            );

            // Track thumbnail for potential rollback
            $this->commitMovedFile($imagePath . '_thumb', $thumbPath);

        } catch (\Throwable $e) {
            Log::warning('Failed to generate thumbnail', [
                'image' => $imagePath,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Rollback previously moved files.
     */
    private function attemptUndoMove(bool $throwOnFailure = true): array
    {
        $results = $this->processAllUndoOperations();

        if (empty($results['failures'])) {
            $this->handleAttemptUndoSuccess();
        } else {
            $this->handleAttemptUndoFailure($results, $throwOnFailure);
        }

        return $results;
    }

    /**
     * Process all pending undo operations.
     */
    private function processAllUndoOperations(): array
    {
        if (!$this->getBreaker()->canAttempt()) {
            $this->handleCircuitBreakerBlock();

            return ['failures' => $this->getMovedFiles(), 'successes' => []];
        }

        $failures = [];
        $successes = [];

        foreach ($this->getMovedFiles() as $sourcePath => $destinationPath) {
            $result = $this->processSingleUndo($sourcePath, $destinationPath);

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
    private function processSingleUndo(string $sourcePath, string $destinationPath): bool
    {
        $attempts = 0;

        while ($attempts < $this->maxAttempts && $this->getBreaker()->canAttempt()) {
            try {
                $this->performUndo($sourcePath, $destinationPath);
                $this->getBreaker()->recordSuccess();

                return true;
            } catch (\Throwable $e) {
                $attempts++;

                $this->handleProcessSingleUndoException(
                    $sourcePath,
                    $destinationPath,
                    $attempts,
                    $e
                );
            }
        }

        if ($this->getBreaker()->maxAttemptsReached($attempts, $this->maxAttempts)) {
            $this->getBreaker()->recordFailure();
        }

        return false;
    }

    /**
     * Execute single file rollback operation.
     */
    private function performUndo(string $sourcePath, string $destinationPath): void
    {
        if (!$this->fileExists($destinationPath)) {
            return; // Nothing to undo
        }

        // If it's a thumbnail, just delete it and return.
        if (str_ends_with($sourcePath, '_thumb')) {
            // Just delete the thumbnail from destination; don't "restore" it to source.
            Storage::disk($this->disk)->delete($destinationPath);
            
            return;
        }

        // Restore original if it doesn't exist
        if (!$this->fileExists($sourcePath)) {
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

    /**
     * Check file existence excluding zero-byte files.
     */
    private function fileExists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path) && Storage::disk($this->disk)->size($path) > 0;
    }

    /**
     * Verify copied file exists at destination.
     */
    private function validateCopiedFile(string $destinationPath): void
    {
        if (!$this->fileExists($destinationPath)) {
            $this->getBreaker()->recordFailure();
            throw new FileValidationException('Copy succeeded but file not found at destination.');
        }
    }

    /**
     * Handle exceptions caught in the attemptMove() method.
     */
    private function handleAttemptMoveException(string $sourcePath, string $destinationDir, \Throwable $exception): void
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
    private function handleProcessMoveException(int $attempts, string $exceptionMessage): void
    {
        Log::warning('Move attempt failed.', [
            'attempt' => $attempts,
            'error' => $exceptionMessage,
        ]);

        if ($this->getBreaker()->maxAttemptsReached($attempts, $this->maxAttempts)) {
            if (!empty($this->getMovedFiles())) {
                try {
                    $this->attemptUndoMove();
                } catch (\Throwable $e) {
                    Log::error('Unexpected exception during undo after move failure.', [
                        'disk' => $this->disk,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } elseif ($this->getBreaker()->canAttempt()) {
            $this->waitBeforeRetry();
        }
    }

    private function handleProcessMoveFailure(int $attempts, ?\Throwable $exception = null): void
    {
        if ($this->getBreaker()->maxAttemptsReached($attempts, $this->maxAttempts)) {
            $this->getBreaker()->recordFailure();
        }

        throw $exception ?? new FileMoveException('Move failed without exception');
    }

    /**
     * Handle exceptions caught during processSingleUndo().
     */
    private function handleProcessSingleUndoException(
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
    private function handleCircuitBreakerBlock(): void
    {
        Log::warning('File move undo blocked by circuit breaker', [
            'disk' => $this->disk,
            'pending_undos' => count($this->getMovedFiles()),
        ]);
    }

    /**
     * Handle successful completion of all undo operations.
     */
    private function handleAttemptUndoSuccess(): void
    {
        $this->clearMovedFiles();
    }

    /**
     * Handle failure during undo operations.
     */
    private function handleAttemptUndoFailure(array $results, bool $throwOnFailure = true): void
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
}
