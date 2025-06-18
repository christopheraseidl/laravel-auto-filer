<?php

namespace christopheraseidl\HasUploads\Jobs\Services;

use christopheraseidl\HasUploads\Jobs\Contracts\CircuitBreaker;
use Illuminate\Support\Facades\Log;

/**
 * Provides common functionality for file operations with circuit breaker protection.
 */
abstract class FileOperator
{
    protected CircuitBreaker $breaker;

    /**
     * Validate that maximum attempts value is at least 1.
     */
    protected function validateMaxAttempts(int $maxAttempts): void
    {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('maxAttempts must be at least 1.');
        }
    }

    /**
     * Check if circuit breaker allows operation to proceed.
     */
    protected function checkCircuitBreaker(string $operation, string $disk, array $context): void
    {
        if (! $this->breaker->canAttempt()) {
            $this->logCircuitBreakerBlock($operation, $disk, $context);

            throw new \Exception('File operations are currently unavailable due to repeated failures. Please try again later.');
        }
    }

    /**
     * Determine if maximum retry attempts have been reached.
     */
    protected function maxAttemptsReached(int $attempts, int $maxAttempts): bool
    {
        return $attempts >= $maxAttempts;
    }

    protected function waitBeforeRetry(): void
    {
        sleep(1); // Brief pause before retry attempt
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
