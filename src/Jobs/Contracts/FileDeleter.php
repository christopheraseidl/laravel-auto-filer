<?php

namespace christopheraseidl\ModelFiler\Jobs\Contracts;

/**
 * Attempts to delete a file with circuit breaker and retry logic.
 */
interface FileDeleter
{
    public function __construct(
        CircuitBreaker $breaker
    );

    /**
     * Attempt file deletion with retry logic and circuit breaker protection.
     */
    public function attemptDelete(string $disk, string $path, int $maxAttempts = 3): bool;

    /**
     * Process single file deletion with retry logic.
     */
    public function processDeletion(string $disk, string $path, int $maxAttempts): bool;

    /**
     * Execute deletion operation and update circuit breaker state.
     */
    public function performDeletion(string $disk, string $path): bool;

    /**
     * Handle the result of a deletion.
     */
    public function handleDeletionResult(bool $result): bool;

    /**
     * Handle exception caught in processDeletion method.
     */
    public function handleProcessDeletionException(string $disk, string $path, int $attempts, int $maxAttempts, string $exceptionMessage): void;

    /**
     * Handle deletion failure between retry attempts.
     */
    public function handleDeletionFailure(string $disk, string $path, int $attempts, ?string $exceptionMessage = null): void;

    /**
     * Delete directory or file based on path type.
     */
    public function deleteDirectoryOrFile(string $disk, string $path): bool;
}
