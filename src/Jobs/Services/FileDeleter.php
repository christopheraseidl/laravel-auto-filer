<?php

namespace christopheraseidl\ModelFiler\Jobs\Services;

use christopheraseidl\ModelFiler\Jobs\Contracts\CircuitBreaker;
use christopheraseidl\ModelFiler\Jobs\Contracts\FileDeleter as FileDeleterContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Handles file deletions with retry logic and circuit breaker protection.
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

        return $this->processDeletion($disk, $path, $maxAttempts);
    }

    /**
     * Process single file deletion with retry logic.
     */
    public function processDeletion(string $disk, string $path, int $maxAttempts): bool
    {
        $lastException = null;
        $attempts = 0;

        while ($attempts < $maxAttempts && $this->getBreaker()->canAttempt()) {
            try {
                return $this->performDeletion($disk, $path);
            } catch (\Exception $e) {
                $attempts++;
                $lastException = $e;

                $this->handleProcessDeletionException($disk, $path, $attempts, $maxAttempts, $lastException->getMessage());
            }
        }

        $this->handleDeletionFailure($disk, $path, $attempts, $lastException?->getMessage());

        return false;
    }

    /**
     * Execute deletion and validate the result.
     */
    public function performDeletion(string $disk, string $path): bool
    {
        $result = $this->deleteDirectoryOrFile($disk, $path);

        return $this->handleDeletionResult($result);
    }

    /**
     * Handle the result of a deletion.
     */
    public function handleDeletionResult(bool $result): bool
    {
        if ($result) {
            $this->getBreaker()->recordSuccess();

            return true;
        } else {
            $this->getBreaker()->recordFailure();

            throw new \Exception('Deletion returned false.');
        }
    }

    /**
     * Handle caught process deletion exception between retry attempts.
     */
    public function handleProcessDeletionException(string $disk, string $path, int $attempts, int $maxAttempts, string $exceptionMessage): void
    {
        Log::warning("File delete attempt {$attempts} failed.", [
            'disk' => $disk,
            'path' => $path,
            'exception' => $exceptionMessage,
            'attempt' => $attempts,
            'max_attempts' => $maxAttempts,
        ]);

        if ($this->getBreaker()->maxAttemptsReached($attempts, $maxAttempts) || ! $this->getBreaker()->canAttempt()) {
            return;
        }

        $this->waitBeforeRetry();
    }

    /**
     * Handle process deletion failure.
     */
    public function handleDeletionFailure(string $disk, string $path, int $attempts, ?string $exceptionMessage = null): void
    {
        $this->getBreaker()->recordFailure();

        Log::error("File deletion failed after {$attempts} attempts.", [
            'disk' => $disk,
            'path' => $path,
            'max_attempts' => $attempts,
            'last_exception' => $exceptionMessage ?? 'File deletion failed with FileDeleter',
        ]);

        throw new \Exception("Failed to delete file after {$attempts} attempts.");
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
