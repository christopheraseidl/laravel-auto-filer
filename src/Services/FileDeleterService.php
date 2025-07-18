<?php

namespace christopheraseidl\AutoFiler\Services;

use christopheraseidl\AutoFiler\Contracts\FileDeleter;
use christopheraseidl\AutoFiler\Exceptions\FileDeleteException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Handles file deletions with retry logic and circuit breaker protection.
 */
class FileDeleterService extends BaseFileOperator implements FileDeleter
{
    /**
     * Delete a file at the specified path.
     */
    public function delete($path): bool
    {
        $this->checkCircuitBreaker('attempt delete file', [
            'path' => $path,
        ]);

        return $this->deleteWithRetries($path);
    }

    /**
     * Attempt file deletion with retry logic and circuit breaker protection.
     */
    protected function deleteWithRetries(string $path): bool
    {
        $lastException = null;
        $attempts = 0;

        while ($attempts < $this->maxAttempts && $this->getBreaker()->canAttempt()) {
            try {
                return $this->executeDeletion($path);
            } catch (\Throwable $e) {
                $attempts++;
                $lastException = $e;

                $this->handleDeletionRetryFailed($path, $attempts, $lastException->getMessage());
            }
        }

        $this->handleAllDeletionAttemptsFailed($path, $attempts, $lastException?->getMessage());

        // This return statement is unreachable
        return false; // @codeCoverageIgnore
    }

    /**
     * Execute deletion and validate the result.
     */
    protected function executeDeletion(string $path): bool
    {
        $result = $this->deleteDirectoryOrFile($path);

        return $this->handleDeletionResult($result);
    }

    /**
     * Delete directory or file based on path type.
     */
    protected function deleteDirectoryOrFile(string $path): bool
    {
        return Storage::disk($this->disk)->directoryExists($path)
            ? Storage::disk($this->disk)->deleteDirectory($path)
            : Storage::disk($this->disk)->delete($path);
    }

    /**
     * Handle the result of a deletion.
     */
    protected function handleDeletionResult(bool $result): bool
    {
        if ($result) {
            $this->getBreaker()->recordSuccess();

            return true;
        } else {
            $this->getBreaker()->recordFailure();

            throw new FileDeleteException('Deletion failed.');
        }
    }

    /**
     * Handle caught process deletion exception between retry attempts.
     */
    protected function handleDeletionRetryFailed(string $path, int $attempts, string $exceptionMessage): void
    {
        Log::warning("File delete attempt {$attempts} failed.", [
            'disk' => $this->disk,
            'path' => $path,
            'exception' => $exceptionMessage,
            'attempt' => $attempts,
            'max_attempts' => $this->maxAttempts,
        ]);

        if (! $this->getBreaker()->canAttempt()) {
            return;
        }

        $this->waitBeforeRetry();
    }

    /**
     * Handle process deletion failure.
     */
    protected function handleAllDeletionAttemptsFailed(string $path, int $attempts, ?string $exceptionMessage = null): void
    {
        Log::error("File deletion failed after {$attempts} attempts.", [
            'disk' => $this->disk,
            'path' => $path,
            'attempts' => $attempts,
            'last_exception' => $exceptionMessage ?? 'File deletion failed with FileDeleter',
        ]);

        throw new FileDeleteException("Failed to delete file after {$attempts} attempts.");
    }
}
