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
     * Execute deletion operation and update circuit breaker state.
     */
    public function performDeletion(string $disk, string $path): bool;

    /**
     * Handle deletion failure between retry attempts.
     */
    public function handleDeletionFailure(int $attempts, int $maxAttempts): void;

    /**
     * Delete directory or file based on path type.
     */
    public function deleteDirectoryOrFile(string $disk, string $path): bool;
}
