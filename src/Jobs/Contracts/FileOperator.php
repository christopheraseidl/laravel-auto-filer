<?php

namespace christopheraseidl\ModelFiler\Jobs\Contracts;

interface FileOperator
{
    /**
     * Validate maximum attempts parameter is within acceptable range.
     */
    public function validateMaxAttempts(int $maxAttempts): void;

    /**
     * Check circuit breaker state before operation.
     */
    public function checkCircuitBreaker(string $operation, string $disk, array $context): void;

    /**
     * Wait before retrying failed operation.
     */
    public function waitBeforeRetry(): void;
}
