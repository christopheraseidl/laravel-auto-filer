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

    public function attemptDelete(string $disk, string $path, int $maxAttempts = 3): bool;
}
