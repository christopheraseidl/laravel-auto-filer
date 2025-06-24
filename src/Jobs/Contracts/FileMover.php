<?php

namespace christopheraseidl\ModelFiler\Jobs\Contracts;

/**
 * Attempts to move a file with circuit breaker, retry, and rollback logic.
 */
interface FileMover
{
    /**
     * Track moved files for rollback.
     */
    public function __construct(
        CircuitBreaker $breaker,
        array $movedFiles = []
    );

    /**
     * Attempt file move with retry logic and automatic rollback on failure.
     */
    public function attemptMove(string $disk, string $oldPath, string $newDir, int $maxAttempts = 3): string;

    /**
     * Attempt rollback of previously moved files.
     */
    public function attemptUndoMove(string $disk, int $maxAttempts = 3, bool $throwOnFailure = true): array;

    /**
     * Process single file move with retry logic.
     */
    public function processMove(string $disk, string $oldPath, string $newPath, int $maxAttempts): string;

    /**
     * Execute file move operation using copy and delete for atomicity.
     */
    public function performMove(string $disk, string $oldPath, string $newPath): string;

    /**
     * Process all pending undo operations.
     */
    public function processAllUndoOperations(string $disk, int $maxAttempts): array;

    /**
     * Attempt rollback of single file move.
     */
    public function processSingleUndo(string $disk, string $oldPath, string $newPath, int $maxAttempts): bool;

    /**
     * Execute single file rollback operation.
     */
    public function performUndo(string $disk, string $oldPath, string $newPath): void;

    /**
     * Execute Laravel Storage method with array of paths.
     */
    public function doStorage(string $disk, string $method, array $paths): void;

    /**
     * Copy file from source to destination.
     */
    public function copyFile(string $disk, string $oldPath, string $newPath): void;

    /**
     * Delete a single file.
     */
    public function deleteFile(string $disk, string $path): void;

    /**
     * Check file existence excluding zero-byte files.
     */
    public function fileExists(string $disk, string $path): bool;

    /**
     * Generate unique filename by appending counter if necessary.
     */
    public function generateUniqueFileName(string $disk, string $path): string;

    /**
     * Build destination path from directory and source filename.
     */
    public function buildNewPath(string $newDir, string $oldPath): string;

    /**
     * Validate storage operation result and handle failure.
     */
    public function validateStorageResult(bool $successful, string $exceptionMessage): void;

    /**
     * Verify copied file exists at destination.
     */
    public function validateCopiedFile(string $disk, string $newPath): void;

    /**
     * Handle exceptions caught in the attemptMove() method.
     */
    public function handleAttemptMoveException(string $disk, string $oldPath, string $newDir, int $maxAttempts, \Throwable $exception): void;

    /**
     * Handle caught storage operation exceptions.
     */
    public function handleStorageException(\Throwable $exception, string $exceptionMessage): void;

    /**
     * Handle failure during move operation with optional rollback.
     */
    public function handleProcessMoveException(string $disk, int $attempts, int $maxAttempts, string $exceptionMessage): void;

    /**
     * Handle exceptions caught during attemptSingleUndo().
     */
    public function handleProcessSingleUndoException(
        string $disk,
        string $oldPath,
        string $newPath,
        int $attempts,
        int $maxAttempts,
        \Throwable $exception
    );

    /**
     * Handle circuit breaker blocking undo operations.
     */
    public function handleCircuitBreakerBlock(string $disk): void;

    /**
     * Handle successful completion of all undo operations.
     */
    public function handleAttemptUndoSuccess(): void;

    /**
     * Handle failure during undo operations.
     */
    public function handleAttemptUndoFailure(string $disk, array $results, bool $throwOnFailure = true): void;

    /**
     * Record successful file move for potential rollback.
     */
    public function commitMovedFile(string $oldPath, string $newPath): string;

    /**
     * Get all tracked file moves.
     */
    public function addToMovedFiles(string $key, string $value): string;

    /**
     * Remove file from rollback tracking.
     */
    public function uncommitMovedFile(string $oldPath): void;

    /**
     * Remove successfully undone files from tracking.
     */
    public function uncommitSuccessfulUndos(array $successes): void;

    /**
     * Clear all tracked file moves.
     */
    public function clearMovedFiles(): void;

    /**
     * Get all tracked file moves.
     */
    public function getMovedFiles(): array;
}
