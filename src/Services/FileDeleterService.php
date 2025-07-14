<?php

namespace christopheraseidl\ModelFiler\Services;

use christopheraseidl\ModelFiler\Contracts\FileDeleter;
use christopheraseidl\ModelFiler\Exceptions\FileDeleteException;
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

        return $this->attemptDelete($path);
    }

    /**
     * Attempt file deletion with retry logic and circuit breaker protection.
     */
    private function attemptDelete(string $path): bool
    {
        $lastException = null;
        $attempts = 0;

        while ($attempts < $this->maxAttempts && $this->getBreaker()->canAttempt()) {
            try {
                return $this->performDeletion($path);
            } catch (\Throwable $e) {
                $attempts++;
                $lastException = $e;

                $this->handleProcessDeletionException($path, $attempts, $lastException->getMessage());
            }
        }

        $this->handleDeletionFailure($path, $attempts, $lastException?->getMessage());

        return false;
    }

    /**
     * Execute deletion and validate the result.
     */
    private function performDeletion(string $path): bool
    {
        $result = $this->deleteDirectoryOrFile($path);

        return $this->handleDeletionResult($result);
    }

    /**
     * Delete directory or file based on path type.
     */
    private function deleteDirectoryOrFile(string $path): bool
    {
        return Storage::disk($this->disk)->directoryExists($path)
            ? Storage::disk($this->disk)->deleteDirectory($path)
            : Storage::disk($this->disk)->delete($path);
    }

    /**
     * Handle the result of a deletion.
     */
    private function handleDeletionResult(bool $result): bool
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
    private function handleProcessDeletionException(string $path, int $attempts, string $exceptionMessage): void
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
    private function handleDeletionFailure(string $path, int $attempts, ?string $exceptionMessage = null): void
    {
        $this->getBreaker()->recordFailure();

        Log::error("File deletion failed after {$attempts} attempts.", [
            'disk' => $this->disk,
            'path' => $path,
            'attempts' => $attempts,
            'last_exception' => $exceptionMessage ?? 'File deletion failed with FileDeleter',
        ]);

        throw new FileDeleteException("Failed to delete file after {$attempts} attempts.");
    }
}
