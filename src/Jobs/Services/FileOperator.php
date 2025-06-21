<?php

namespace christopheraseidl\ModelFiler\Jobs\Services;

use christopheraseidl\ModelFiler\Contracts\Loggable;
use christopheraseidl\ModelFiler\Contracts\WithCircuitBreaker;
use christopheraseidl\ModelFiler\Jobs\Contracts\CircuitBreaker;
use christopheraseidl\ModelFiler\Jobs\Contracts\FileOperator as FileOperatorContract;
use christopheraseidl\ModelFiler\Traits\HasCircuitBreaker;
use christopheraseidl\ModelFiler\Traits\InteractsWithLog;

/**
 * Provides common functionality for file operations with circuit breaker protection.
 */
abstract class FileOperator implements FileOperatorContract, Loggable, WithCircuitBreaker
{
    use HasCircuitBreaker, InteractsWithLog;

    protected CircuitBreaker $breaker;

    /**
     * Validate that maximum attempts value is at least 1.
     */
    public function validateMaxAttempts(int $maxAttempts): void
    {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('maxAttempts must be at least 1.');
        }
    }

    /**
     * Check if circuit breaker allows operation to proceed.
     */
    public function checkCircuitBreaker(string $operation, string $disk, array $context): void
    {
        if (! $this->breaker->canAttempt()) {
            $this->logWarning('File operation blocked by circuit breaker.', [
                'operation' => $operation,
                'disk' => $disk,
                'breaker_stats' => $this->breaker->getStats(),
                ...$context,
            ]);

            throw new \Exception('File operations are currently unavailable due to repeated failures. Please try again later.');
        }
    }

    public function waitBeforeRetry(): void
    {
        sleep(1); // Brief pause before retry attempt
    }
}
