<?php

namespace christopheraseidl\ModelFiler\Jobs\Services;

use christopheraseidl\ModelFiler\Contracts\Cacheable;
use christopheraseidl\ModelFiler\Contracts\Loggable;
use christopheraseidl\ModelFiler\Jobs\Contracts\CircuitBreaker as CircuitBreakerContract;
use christopheraseidl\ModelFiler\Traits\InteractsWithCache;
use christopheraseidl\ModelFiler\Traits\InteractsWithLog;
use Illuminate\Support\Facades\Mail;

/**
 * Prevents cascading failures by monitoring failure rates and blocking requests when thresholds are exceeded.
 */
class CircuitBreaker implements Cacheable, CircuitBreakerContract, Loggable
{
    use InteractsWithCache;
    use InteractsWithLog;

    const STATE_CLOSED = 'closed';

    const STATE_OPEN = 'open';

    const STATE_HALF_OPEN = 'half_open';

    public function __construct(
        private readonly string $name,
        private readonly int $failureThreshold = 5,
        private readonly int $recoveryTimeout = 60,
        private readonly int $halfOpenMaxAttempts = 3,
        private readonly int $cacheTtlHours = 1,
        private readonly bool $emailNotificationEnabled = false,
        private readonly ?string $adminEmail = null
    ) {}

    /**
     * Check if circuit is in closed state.
     */
    public function isClosed(): bool
    {
        return $this->getState() === self::STATE_CLOSED;
    }

    /**
     * Check if circuit is in open state.
     */
    public function isOpen(): bool
    {
        return $this->getState() === self::STATE_OPEN;
    }

    /**
     * Check if circuit is in half-open state.
     */
    public function isHalfOpen(): bool
    {
        return $this->getState() === self::STATE_HALF_OPEN;
    }

    /**
     * Determine if operation can be attempted in current state.
     */
    public function canAttempt(): bool
    {
        try {
            if ($this->isClosed()) {
                return true;
            }

            if ($this->isOpen()) {
                $openedAt = $this->cacheGet($this->getKey('opened_at'));

                if ($openedAt && (now()->timestamp - $openedAt) >= $this->recoveryTimeout) {
                    $this->transitionToHalfOpen();

                    return true;
                }

                return false;
            }

            if ($this->isHalfOpen()) {
                $attempts = $this->cacheGet($this->getKey('half_open_attempts'), 0);

                return $attempts < $this->halfOpenMaxAttempts;
            }

            return false;
        } catch (\Exception $e) {
            $this->logWarning('CircuitBreaker cache failure, failing open', [
                'breaker' => $this->name,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Record successful operation.
     */
    public function recordSuccess(): void
    {
        try {
            if ($this->isHalfOpen()) {
                $this->transitionToClosed();
                $this->logInfo("CircuitBreaker '{$this->name}' recovered and transitioned to CLOSED state at {$this->getTimestamp()}.");
            }

            $this->cacheForget($this->getKey('failures'));
        } catch (\Exception $e) {
            $this->logWarning('CircuitBreaker cache failure on recordSuccess', [
                'breaker' => $this->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record failed operation.
     */
    public function recordFailure(): void
    {
        try {
            $failures = $this->cacheGet($this->getKey('failures'), 0) + 1;
            $this->setKey('failures', $failures);

            if ($this->isClosed() && $failures >= $this->failureThreshold) {
                $this->transitionToOpen();
                $this->sendAdminNotification("Circuit breaker opened after {$failures} failures.");
            } elseif ($this->isHalfOpen()) {
                $this->cacheIncrement($this->getKey('half_open_attempts'));
                $halfOpenAttempts = $this->cacheGet($this->getKey('half_open_attempts'), 0);

                if ($halfOpenAttempts >= $this->halfOpenMaxAttempts) {
                    $this->transitionToOpen();
                    $this->sendAdminNotification("Circuit breaker reopened after {$halfOpenAttempts} half-open attempts.");
                }
            }
        } catch (\Exception $e) {
            $this->logWarning('CircuitBreaker cache failure during recordFailure', [
                'breaker' => $this->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reset circuit to closed state.
     */
    public function reset(): void
    {
        try {
            $this->transitionToClosed();
            $this->logInfo("CircuitBreaker '{$this->name}' manually reset to CLOSED state at {$this->getTimestamp()}.");
        } catch (\Exception $e) {
            $this->logWarning('CircuitBreaker cache failure during reset', [
                'breaker' => $this->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Return current state as string.
     */
    public function getState(): string
    {
        return $this->cacheGet($this->getKey('state'), self::STATE_CLOSED);
    }

    /**
     * Return current failure count.
     */
    public function getFailureCount(): int
    {
        return $this->cacheGet($this->getKey('failures'), 0);
    }

    /**
     * Return circuit breaker statistics.
     */
    public function getStats(): array
    {
        return [
            'name' => $this->name,
            'state' => $this->getState(),
            'failure_count' => $this->getFailureCount(),
            'failure_threshold' => $this->failureThreshold,
            'opened_at' => $this->cacheGet($this->getKey('opened_at')),
            'recovery_timeout' => $this->recoveryTimeout,
        ];
    }

    /**
     * Determine if maximum retry attempts have been reached.
     */
    public function maxAttemptsReached(int $attempts, int $maxAttempts): bool
    {
        return $attempts >= $maxAttempts;
    }

    /**
     * Transition circuit to open state when failure threshold is exceeded.
     */
    public function transitionToOpen(): void
    {
        $this->setKey('state', self::STATE_OPEN);
        $this->setKey('opened_at', now()->timestamp);
        $this->cacheForget($this->getKey('half_open_attempts'));

        $this->logWarning("CircuitBreaker '{$this->name}' transitioned to OPEN state at {$this->getTimestamp()}.", $this->getStats());
    }

    /**
     * Transition circuit to half-open state for recovery testing.
     */
    public function transitionToHalfOpen(): void
    {
        $this->setKey('state', self::STATE_HALF_OPEN);
        $this->setKey('half_open_attempts', 0);

        $this->logWarning("CircuitBreaker '{$this->name}' transitioned to HALF_OPEN state at {$this->getTimestamp()}.", $this->getStats());
    }

    /**
     * Transition circuit to closed state after successful recovery.
     */
    public function transitionToClosed(): void
    {
        $this->cacheForget($this->getKey('state'));
        $this->cacheForget($this->getKey('failures'));
        $this->cacheForget($this->getKey('opened_at'));
        $this->cacheForget($this->getKey('half_open_attempts'));

        $this->logInfo("CircuitBreaker '{$this->name}' transitioned to CLOSED state at {$this->getTimestamp()}.", $this->getStats());
    }

    /**
     * Generate cache key for storing circuit breaker state data.
     */
    public function getKey(string $name): string
    {
        return "circuit_breaker:{$this->name}:$name";
    }

    /**
     * Store value in cache with configured TTL.
     */
    public function setKey(string $name, mixed $value, ?\DateTime $time = null): void
    {
        $time = $time ?? now()->addHours($this->cacheTtlHours);

        $this->cachePut($this->getKey($name), $value, $time);
    }

    /**
     * Return current timestamp in standardized format.
     */
    public function getTimestamp(): string
    {
        return now()->format('Y-m-d H:i:s T');
    }

    /**
     * Send admin notification about circuit breaker state changes.
     */
    public function sendAdminNotification(string $message): void
    {
        if (! $this->emailNotificationEnabled || ! $this->adminEmail) {
            return;
        }

        try {
            $stats = $this->getStats();
            $subject = "Circuit breaker alert: {$this->name}";

            Mail::raw(
                $this->buildEmailContent($message, $stats),
                function ($mail) use ($subject) {
                    $mail->to($this->adminEmail)
                        ->subject($subject);
                }
            );

            $this->logInfo('Circuit breaker notification sent to admin.', [
                'breaker' => $this->name,
            ]);
        } catch (\Exception $e) {
            $this->logError('Failed to send circuit breaker notification.', [
                'breaker' => $this->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build email content for admin notifications with circuit breaker details.
     */
    public function buildEmailContent(string $message, array $stats): string
    {
        $appName = config()->get('model-filer.name', 'Laravel application');

        return "
        Circuit breaker alert: {$appName}
        Time: {$this->getTimestamp()}

        {$message}

        Details:
        - Name: {$stats['name']} 
        - Current state: {$stats['state']}
        - Failure count: {$stats['failure_count']} / {$stats['failure_threshold']}
        - Recovery timeout: {$stats['recovery_timeout']} seconds

        This is an automatic notification. Please check the application logs for more details.
        ";
    }
}
