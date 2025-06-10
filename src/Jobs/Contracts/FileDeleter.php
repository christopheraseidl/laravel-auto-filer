<?php

namespace christopheraseidl\HasUploads\Jobs\Contracts;

interface FileDeleter
{
    public function __construct(
        CircuitBreaker $breaker
    );

    public function attemptDelete(string $disk, string $path, int $maxAttempts = 3): bool;
}
