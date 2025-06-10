<?php

namespace christopheraseidl\HasUploads\Jobs\Contracts;

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
