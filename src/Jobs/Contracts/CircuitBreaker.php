<?php

namespace christopheraseidl\HasUploads\Jobs\Contracts;

/**
 * Contract for circuit breaker implementations that prevent cascading failures.
 *
 * Circuit breakers monitor failure rates and temporarily block operations
 * when failure thresholds are exceeded, allowing systems to recover.
 */
interface CircuitBreaker
{
    public function __construct(
        string $name,
        int $failureThreshold,
        int $recoveryTimeout,
        int $halfOpenMaxAttempts,
        int $cacheTtlHours,
        bool $emailNotificationEnabled,
        ?string $adminEmail
    );

    public function isClosed(): bool;

    public function isOpen(): bool;

    public function isHalfOpen(): bool;

    public function canAttempt(): bool;

    public function recordSuccess(): void;

    public function recordFailure(): void;

    public function reset(): void;

    public function getState(): string;

    public function getFailureCount(): int;

    public function getStats(): array;
}
