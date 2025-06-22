<?php

namespace christopheraseidl\ModelFiler\Jobs\Services;

use christopheraseidl\ModelFiler\Jobs\Contracts\CircuitBreaker;
use christopheraseidl\ModelFiler\Jobs\Contracts\FileDeleter as FileDeleterContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Handles file deletion operations with retry logic and circuit breaker protection.
 */
class FileDeleter extends FileOperator implements FileDeleterContract
{
    public function __construct(
        protected CircuitBreaker $breaker
    ) {}

    /**
     * Attempt file deletion with retry logic and circuit breaker protection.
     */
    public function attemptDelete(string $disk, string $path, int $maxAttempts = 3): bool
    {
        $this->validateMaxAttempts($maxAttempts);
        $this->checkCircuitBreaker('attempt delete file', $disk, [
            'path' => $path,
        ]);

        $lastException = null;
        $attempts = 0;

        while ($attempts < $maxAttempts && $this->getBreaker()->canAttempt()) {
            try {
                return $this->performDeletion($disk, $path);
            } catch (\Exception $e) {
                $attempts++;
                $lastException = $e;

                Log::warning("File delete attempt {$attempts} failed.", [
                    'disk' => $disk,
                    'path' => $path,
                    'error' => $e->getMessage(),
                    'attempt' => $attempts,
                    'max_attempts' => $maxAttempts,
                ]);

                $this->handleDeletionFailure($attempts, $maxAttempts);
            }
        }

        $this->getBreaker()->recordFailure();

        Log::error("File deletion failed after {$attempts} attempts.", [
            'disk' => $disk,
            'path' => $path,
            'max_attempts' => $attempts,
            'last_error' => $lastException?->getMessage(),
        ]);

        throw new \Exception("Failed to delete file after {$attempts} attempts.");
    }

    /**
     * Execute deletion operation and update circuit breaker state.
     */
    public function performDeletion(string $disk, string $path): bool
    {
        $result = $this->deleteDirectoryOrFile($disk, $path);

        if ($result) {
            $this->getBreaker()->recordSuccess();

            return true;
        } else {
            $this->getBreaker()->recordFailure();

            throw new \Exception('Deletion operation returned false.');
        }
    }

    /**
     * Handle deletion failure between retry attempts.
     */
    public function handleDeletionFailure(int $attempts, int $maxAttempts): void
    {
        if ($this->getBreaker()->maxAttemptsReached($attempts, $maxAttempts) || ! $this->getBreaker()->canAttempt()) {
            return;
        }

        $this->waitBeforeRetry();
    }

    /**
     * Delete directory or file based on path type.
     */
    public function deleteDirectoryOrFile(string $disk, string $path): bool
    {
        return Storage::disk($disk)->directoryExists($path)
            ? Storage::disk($disk)->deleteDirectory($path)
            : Storage::disk($disk)->delete($path);
    }
}
