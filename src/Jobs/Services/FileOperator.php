<?php

namespace christopheraseidl\HasUploads\Jobs\Services;

use christopheraseidl\HasUploads\Jobs\Contracts\CircuitBreaker;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base class for file operations with circuit breaker protection.
 *
 * Provides common functionality for file operations including retry logic,
 * circuit breaker integration, and failure handling to prevent cascading
 * failures in file system operations.
 */
abstract class FileOperator
{
    protected CircuitBreaker $breaker;

    /**
     * Validate that the maximum attempts value is reasonable.
     *
     * @throws \InvalidArgumentException When maxAttempts is less than 1
     */
    protected function validateMaxAttempts(int $maxAttempts): void
    {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('maxAttempts must be at least 1.');
        }
    }

    /**
     * Check if the circuit breaker allows the operation to proceed.
     *
     * If the circuit breaker is open (blocking requests), logs the blocked
     * operation and throws an exception to prevent the operation from executing.
     *
     * @throws \Exception When circuit breaker is open and blocking operations
     */
    protected function checkCircuitBreaker(string $operation, string $disk, array $context): void
    {
        if (! $this->breaker->canAttempt()) {
            $this->logCircuitBreakerBlock($operation, $disk, $context);

            throw new \Exception('File operations are currently unavailable due to repeated failures. Please try again later.');
        }
    }

    /**
     * Determine if the maximum number of retry attempts has been reached.
     *
     * @return bool True if no more attempts should be made
     */
    protected function maxAttemptsReached(int $attempts, int $maxAttempts): bool
    {
        return $attempts >= $maxAttempts;
    }

    protected function waitBeforeRetry(): void
    {
        sleep(1);
    }

    protected function logCircuitBreakerBlock(string $operation, string $disk, array $context): void
    {
        Log::warning('File operation blocked by circuit breaker.', [
            'operation' => $operation,
            'disk' => $disk,
            'breaker_stats' => $this->breaker->getStats(),
            ...$context,
        ]);
    }
}
