<?php

namespace christopheraseidl\ModelFiler\Jobs\Contracts;

/**
 * Attempts to move a file with circuit breaker, retry, and rollback logic.
 */
interface FileMover
{
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
     * Execute file move operation using copy and delete for atomicity.
     */
    public function performMove(string $disk, string $oldPath, string $newPath): string;

    /**
     * Copy file from source to destination.
     */
    public function copyFile(string $disk, string $oldPath, string $newPath): void;

    /**
     * Verify copied file exists at destination.
     */
    public function validateCopiedFile(string $disk, string $newPath): void;

    /**
     * Process all pending undo operations.
     */
    public function processUndoOperations(string $disk, int $maxAttempts): array;

    /**
     * Attempt rollback of single file move.
     */
    public function attemptSingleUndo(string $disk, string $oldPath, string $newPath, int $maxAttempts): bool;

    /**
     * Execute single file rollback operation.
     */
    public function performUndo(string $disk, string $oldPath, string $newPath): void;

    /**
     * Handle failure during move operation with optional rollback.
     */
    public function handleMoveFailure(string $disk, int $attempts, int $maxAttempts): void;

    /**
     * Handle successful completion of all undo operations.
     */
    public function handleSuccessfulUndo(): void;

    /**
     * Handle failure during undo operations.
     */
    public function handleUndoFailure(string $disk, array $results, bool $throwOnFailure = true): void;

    /**
     * Remove successfully undone files from tracking.
     */
    public function uncommitSuccessfulUndos(array $successes): void;

    /**
     * Build destination path from directory and source filename.
     */
    public function buildNewPath(string $newDir, string $oldPath): string;

    /**
     * Check file existence excluding zero-byte files.
     */
    public function exists(string $disk, string $path): bool;

    /**
     * Generate unique filename by appending counter if necessary.
     */
    public function generateUniqueFileName(string $disk, string $path): string;

    /**
     * Record successful file move for potential rollback.
     */
    public function commitMovedFile(string $oldPath, string $newPath): string;

    /**
     * Remove file from rollback tracking.
     */
    public function uncommitMovedFile(string $oldPath): void;

    /**
     * Clear all tracked file moves.
     */
    public function clearMovedFiles(): void;

    /**
     * Get all tracked file moves.
     */
    public function getMovedFiles(): array;
}
