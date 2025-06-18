<?php

namespace christopheraseidl\HasUploads\Jobs\Services;

use christopheraseidl\HasUploads\Jobs\Contracts\CircuitBreaker;
use christopheraseidl\HasUploads\Jobs\Contracts\FileDeleter as FileDeleterContract;
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
     * Attempt to delete a file with retry logic.
     */
    public function attemptDelete(string $disk, string $path, int $maxAttempts = 3): bool
    {
        $this->validateMaxAttempts($maxAttempts);
        $this->checkCircuitBreaker('attempt delete file', $disk, [
            'path' => $path,
        ]);

        $lastException = null;
        $attempts = 0;

        while ($attempts < $maxAttempts && $this->breaker->canAttempt()) {
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

        $this->breaker->recordFailure();

        Log::error("File deletion failed after {$attempts} attempts.", [
            'disk' => $disk,
            'path' => $path,
            'max_attempts' => $attempts,
            'last_error' => $lastException?->getMessage(),
        ]);

        throw new \Exception("Failed to delete file after {$attempts} attempts.");
    }

    protected function performDeletion(string $disk, string $path): bool
    {
        $result = $this->deleteDirectoryOrFile($disk, $path);

        if ($result) {
            $this->breaker->recordSuccess();

            return true;
        } else {
            $this->breaker->recordFailure();

            throw new \Exception('Deletion operation returned false.');
        }
    }

    protected function handleDeletionFailure(int $attempts, int $maxAttempts): void
    {
        if ($this->breaker->maxAttemptsReached($attempts, $maxAttempts) || ! $this->breaker->canAttempt()) {
            return;
        }

        $this->waitBeforeRetry();
    }

    protected function deleteDirectoryOrFile(string $disk, string $path): bool
    {
        return Storage::disk($disk)->directoryExists($path)
            ? Storage::disk($disk)->deleteDirectory($path)
            : Storage::disk($disk)->delete($path);
    }
}
