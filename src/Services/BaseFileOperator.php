<?php

namespace christopheraseidl\ModelFiler\Services;

use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;
use christopheraseidl\CircuitBreaker\Exceptions\CircuitBreakerException;
use Illuminate\Support\Facades\Log;

/**
 * Provides common functionality for file operations with circuit breaker protection.
 */
abstract class BaseFileOperator
{
    protected readonly string $disk;

    protected readonly int $maxAttempts;

    /**
     * Track moved files for rollback (source path => destination path).
     */
    public function __construct(protected CircuitBreakerContract $breaker)
    {
        $this->disk = config('model-filer.disk', 'public');

        $this->maxAttempts = config('model-filer.maximum_file_operation_retries', 3);

        $this->validateMaxAttempts($this->maxAttempts);
    }

    /**
     * Return the circuit breaker instance.
     */
    public function getBreaker(): CircuitBreakerContract
    {
        return $this->breaker;
    }

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
    protected function checkCircuitBreaker(string $operation, array $context): void
    {
        if (! $this->getBreaker()->canAttempt()) {
            Log::warning('File operation blocked by circuit breaker.', [
                'operation' => $operation,
                'disk' => $this->disk,
                'breaker_stats' => $this->breaker->getStats(),
                ...$context,
            ]);

            throw new CircuitBreakerException('File operations are currently unavailable due to repeated failures. Please try again later.');
        }
    }

    protected function waitBeforeRetry(): void
    {
        sleep(1); // Brief pause before retry attempt
    }
}
