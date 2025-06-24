<?php

namespace christopheraseidl\ModelFiler\Jobs\Services;

use christopheraseidl\ModelFiler\Jobs\Contracts\CircuitBreaker as CircuitBreakerContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Prevents cascading failures by monitoring failure rates and blocking requests when thresholds are exceeded.
 */
class CircuitBreaker implements CircuitBreakerContract
{
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
                if ($this->timeoutHasPassed()) {
                    $this->transitionToHalfOpen();

                    return true;
                }

                return false;
            }

            if ($this->isHalfOpen()) {
                return ! $this->maxHalfOpenAttemptsReached();
            }

            return false;
        } catch (\Exception $e) {
            Log::warning('CircuitBreaker cache failure, failing open', [
                'breaker' => $this->name,
                'exception' => $e->getMessage(),
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
                Log::info("CircuitBreaker '{$this->name}' recovered and transitioned to CLOSED state at {$this->getTimestamp()}.");
            }

            Cache::forget($this->getKey('failures'));
        } catch (\Exception $e) {
            Log::warning('CircuitBreaker cache failure on recordSuccess', [
                'breaker' => $this->name,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record failed operation.
     */
    public function recordFailure(): void
    {
        try {
            $failures = Cache::get($this->getKey('failures'), 0) + 1;
            $this->setKey('failures', $failures);

            if ($this->isClosed() && $this->maxAttemptsReached($failures, $this->failureThreshold)) {
                $this->transitionToOpen();
                $this->sendAdminNotification("Circuit breaker opened after {$failures} failures.");
            } elseif ($this->isHalfOpen()) {
                Cache::increment($this->getKey('half_open_attempts'));
                $halfOpenAttempts = Cache::get($this->getKey('half_open_attempts'), 0);

                if ($this->maxAttemptsReached($halfOpenAttempts, $this->halfOpenMaxAttempts)) {
                    $this->transitionToOpen();
                    $this->sendAdminNotification("Circuit breaker reopened after {$halfOpenAttempts} half-open attempts.");
                }
            }
        } catch (\Exception $e) {
            Log::warning('CircuitBreaker cache failure during recordFailure', [
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
            Log::info("CircuitBreaker '{$this->name}' manually reset to CLOSED state at {$this->getTimestamp()}.");
        } catch (\Exception $e) {
            Log::warning('CircuitBreaker cache failure during reset', [
                'breaker' => $this->name,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Return current state as string.
     */
    public function getState(): string
    {
        return Cache::get($this->getKey('state'), self::STATE_CLOSED);
    }

    /**
     * Return current failure count.
     */
    public function getFailureCount(): int
    {
        return Cache::get($this->getKey('failures'), 0);
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
            'opened_at' => Cache::get($this->getKey('opened_at')),
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
     * Determine if maximum half-open retry attempts have been reached.
     */
    public function maxHalfOpenAttemptsReached(): bool
    {
        $attempts = Cache::get($this->getKey('half_open_attempts'), 0);

        return $this->maxAttemptsReached($attempts, $this->halfOpenMaxAttempts);
    }

    /**
     * Transition circuit to open state when failure threshold is exceeded.
     */
    public function transitionToOpen(): void
    {
        $this->setKey('state', self::STATE_OPEN);
        $this->setKey('opened_at', now()->timestamp);
        Cache::forget($this->getKey('half_open_attempts'));

        Log::warning("CircuitBreaker '{$this->name}' transitioned to OPEN state at {$this->getTimestamp()}.", $this->getStats());
    }

    /**
     * Transition circuit to half-open state for recovery testing.
     */
    public function transitionToHalfOpen(): void
    {
        $this->setKey('state', self::STATE_HALF_OPEN);
        $this->setKey('half_open_attempts', 0);

        Log::warning("CircuitBreaker '{$this->name}' transitioned to HALF_OPEN state at {$this->getTimestamp()}.", $this->getStats());
    }

    /**
     * Transition circuit to closed state after successful recovery.
     */
    public function transitionToClosed(): void
    {
        Cache::forget($this->getKey('state'));
        Cache::forget($this->getKey('failures'));
        Cache::forget($this->getKey('opened_at'));
        Cache::forget($this->getKey('half_open_attempts'));

        Log::info("CircuitBreaker '{$this->name}' transitioned to CLOSED state at {$this->getTimestamp()}.", $this->getStats());
    }

    /**
     * Determine whether the timeout period has passed.
     */
    public function timeoutHasPassed(): bool
    {
        $openedAt = Cache::get($this->getKey('opened_at'));

        if ($openedAt && (now()->timestamp - $openedAt) >= $this->recoveryTimeout) {
            $this->transitionToHalfOpen();

            return true;
        }

        return false;
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

        Cache::put($this->getKey($name), $value, $time);
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
        if (! $this->isEmailNotificationEnabled() || ! $this->isValidEmail()) {
            return;
        }

        try {
            $stats = $this->getStats();
            $subject = "Circuit breaker alert: {$this->name}";

            Mail::raw(
                $this->buildEmailContent($message, $stats),
                function ($mail) use ($subject) {
                    $mail->to($this->getAdminEmail())
                        ->subject($subject);
                }
            );

            Log::info('Circuit breaker notification sent to admin.', [
                'breaker' => $this->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send circuit breaker notification.', [
                'breaker' => $this->name,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine whether email notification is enabled.
     */
    public function isEmailNotificationEnabled(): bool
    {
        return $this->emailNotificationEnabled;
    }

    /**
     * Determine whether email exists and is valid.
     */
    public function isValidEmail(): bool
    {
        if (! $this->adminEmail) {
            return false;
        }

        return filter_var($this->adminEmail, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Get the admin email.
     */
    public function getAdminEmail(): ?string
    {
        return $this->adminEmail;
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
