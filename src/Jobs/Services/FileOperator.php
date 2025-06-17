<?php

namespace christopheraseidl\HasUploads\Jobs\Services;

use christopheraseidl\HasUploads\Jobs\Contracts\CircuitBreaker;
use Illuminate\Support\Facades\Log;

abstract class FileOperator
{
    protected CircuitBreaker $breaker;

    protected function validateMaxAttempts(int $maxAttempts): void
    {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('maxAttempts must be at least 1.');
        }
    }

    protected function checkCircuitBreaker(string $operation, string $disk, array $context): void
    {
        if (! $this->breaker->canAttempt()) {
            $this->logCircuitBreakerBlock($operation, $disk, $context);

            throw new \Exception('File operations are currently unavailable due to repeated failures. Please try again later.');
        }
    }

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
