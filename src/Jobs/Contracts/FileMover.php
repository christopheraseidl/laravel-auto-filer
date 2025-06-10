<?php

namespace christopheraseidl\HasUploads\Jobs\Contracts;

interface FileMover
{
    public function __construct(
        CircuitBreaker $breaker,
        array $movedFiles = []
    );

    public function attemptMove(string $disk, string $oldPath, string $newDir, int $maxAttempts = 3): string;

    public function attemptUndoMove(string $disk, int $maxAttempts = 3): array;
}
