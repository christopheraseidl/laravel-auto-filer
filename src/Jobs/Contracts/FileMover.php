<?php

namespace christopheraseidl\HasUploads\Jobs\Contracts;

/**
 * Attempts to move a file with circuit breaker, retry, and rollback logic.
 */
interface FileMover
{
    public function __construct(
        CircuitBreaker $breaker,
        array $movedFiles = []
    );

    public function attemptMove(string $disk, string $oldPath, string $newDir, int $maxAttempts = 3): string;

    public function attemptUndoMove(string $disk, int $maxAttempts = 3): array;
}
