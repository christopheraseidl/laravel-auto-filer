<?php

namespace christopheraseidl\AutoFiler\Contracts;

/**
 * Deletes a file or directory at the provided path.
 */
interface FileDeleter
{
    /**
     * Attempt file deletion with retry logic and circuit breaker protection.
     */
    public function delete(string $path): bool;
}
