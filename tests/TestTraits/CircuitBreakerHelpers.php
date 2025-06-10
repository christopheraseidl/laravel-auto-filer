<?php

namespace christopheraseidl\HasUploads\Tests\TestTraits;

use Illuminate\Support\Facades\Cache;

trait CircuitBreakerHelpers
{
    // Helper methods to reduce test complexity and improve readability
    public function setFailureCount(int $count, string $circuitName = 'test-circuit'): void
    {
        Cache::put("circuit_breaker:{$circuitName}:failures", $count, now()->addHour());
    }

    public function getHalfOpenAttempts(string $circuitName = 'test-circuit'): int
    {
        return Cache::get("circuit_breaker:{$circuitName}:half_open_attempts", 0);
    }

    public function setHalfOpenAttempts(int $count, string $circuitName = 'test-circuit'): void
    {
        Cache::put("circuit_breaker:{$circuitName}:half_open_attempts", $count, now()->addHour());
    }

    public function transitionToOpen(string $circuitName = 'test-circuit'): void
    {
        Cache::put("circuit_breaker:{$circuitName}:state", 'open', now()->addHour());
        Cache::put("circuit_breaker:{$circuitName}:opened_at", now()->timestamp, now()->addHour());
        Cache::put("circuit_breaker:{$circuitName}:failures", 2, now()->addHour());
    }

    public function transitionToHalfOpen(string $circuitName = 'test-circuit'): void
    {
        Cache::put("circuit_breaker:{$circuitName}:state", 'half_open', now()->addHour());
        Cache::put("circuit_breaker:{$circuitName}:half_open_attempts", 0, now()->addHour());
    }

    public function transitionToClosed(string $circuitName = 'test-circuit'): void
    {
        Cache::forget("circuit_breaker:{$circuitName}:state");
        Cache::forget("circuit_breaker:{$circuitName}:failures");
        Cache::forget("circuit_breaker:{$circuitName}:opened_at");
        Cache::forget("circuit_breaker:{$circuitName}:half_open_attempts");
    }
}
