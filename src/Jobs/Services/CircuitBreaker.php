<?php

namespace christopheraseidl\HasUploads\Jobs\Services;

use christopheraseidl\HasUploads\Jobs\Contracts\CircuitBreaker as CircuitBreakerContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Circuit breaker implementation that prevents cascading failures by monitoring
 * failure rates and temporarily blocking requests when thresholds are exceeded.
 *
 * The circuit breaker has three states:
 * - CLOSED: Normal operation, requests are allowed through
 * - OPEN: Failure threshold exceeded, requests are blocked
 * - HALF_OPEN: Testing phase after recovery timeout, limited requests allowed
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
     * Check if the circuit breaker is in the closed state (normal operation).
     */
    public function isClosed(): bool
    {
        return $this->getState() === self::STATE_CLOSED;
    }

    /**
     * Check if the circuit breaker is in the open state (blocking requests).
     */
    public function isOpen(): bool
    {
        return $this->getState() === self::STATE_OPEN;
    }

    /**
     * Check if the circuit breaker is in the half-open state (testing recovery).
     */
    public function isHalfOpen(): bool
    {
        return $this->getState() === self::STATE_HALF_OPEN;
    }

    /**
     * Determine if a request attempt is allowed based on the current state and conditions.
     *
     * In CLOSED state, all attempts are allowed.
     * In OPEN state, attempts are only allowed after the recovery timeout has passed.
     * In HALF_OPEN state, attempts are limited to the configured maximum.
     *
     * @return bool True if the attempt should be allowed, false otherwise
     */
    public function canAttempt(): bool
    {
        try {
            $state = $this->getState();

            if ($state === self::STATE_CLOSED) {
                return true;
            }

            if ($state === self::STATE_OPEN) {
                $openedAt = Cache::get($this->getKey('opened_at'));

                if ($openedAt && (now()->timestamp - $openedAt) >= $this->recoveryTimeout) {
                    $this->transitionToHalfOpen();

                    return true;
                }

                return false;
            }

            if ($state === self::STATE_HALF_OPEN) {
                $attempts = Cache::get($this->getKey('half_open_attempts'), 0);

                return $attempts < $this->halfOpenMaxAttempts;
            }

            return false;
        } catch (\Exception $e) {
            Log::warning('CircuitBreaker cache failure, failing open', [
                'breaker' => $this->name,
                'error' => $e->getMessage(),
            ]);

            return true;
        }
    }

    /**
     * Record a successful operation and potentially transition from HALF_OPEN to CLOSED.
     *
     * If the circuit breaker is in HALF_OPEN state, a success will transition it back
     * to CLOSED state, indicating full recovery.
     */
    public function recordSuccess(): void
    {
        try {
            $state = $this->getState();

            if ($state === self::STATE_HALF_OPEN) {
                $this->transitionToClosed();
                Log::info("CircuitBreaker '{$this->name}' recovered and transitioned to CLOSED state at {$this->getTimestamp()}.");
            }

            Cache::forget($this->getKey('failures'));
        } catch (\Exception $e) {
            Log::warning('CircuitBreaker cache failure on recordSuccess', [
                'breaker' => $this->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record a failure and potentially transition the circuit breaker state.
     *
     * In CLOSED state, failures are counted and may trigger transition to OPEN.
     * In HALF_OPEN state, failures increment attempts and may trigger transition back to OPEN.
     */
    public function recordFailure(): void
    {
        try {
            $state = $this->getState();
            $failures = Cache::get($this->getKey('failures'), 0) + 1;
            $this->setKey('failures', $failures);

            if ($state === self::STATE_CLOSED && $failures >= $this->failureThreshold) {
                $this->transitionToOpen();
                $this->sendAdminNotification("Circuit breaker opened after {$failures} failures.");
            } elseif ($state === self::STATE_HALF_OPEN) {
                Cache::increment($this->getKey('half_open_attempts'));
                $halfOpenAttempts = Cache::get($this->getKey('half_open_attempts'), 0);

                if ($halfOpenAttempts >= $this->halfOpenMaxAttempts) {
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
     * Manually reset the circuit breaker to CLOSED state, clearing all failure data.
     */
    public function reset(): void
    {
        try {
            $this->transitionToClosed();
            Log::info("CircuitBreaker '{$this->name}' manually reset to CLOSED state at {$this->getTimestamp()}.");
        } catch (\Exception $e) {
            Log::warning('CircuitBreaker cache failure during reset', [
                'breaker' => $this->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the current state of the circuit breaker.
     *
     * @return string The current state (closed, open, or half_open)
     */
    public function getState(): string
    {
        return Cache::get($this->getKey('state'), self::STATE_CLOSED);
    }

    /**
     * Get the current failure count.
     *
     * @return int Number of recorded failures
     */
    public function getFailureCount(): int
    {
        return Cache::get($this->getKey('failures'), 0);
    }

    /**
     * Get comprehensive statistics about the circuit breaker's current state.
     *
     * @return array Associative array containing name, state, failure counts, and timing information
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

    private function transitionToOpen(): void
    {
        $this->setKey('state', self::STATE_OPEN);
        $this->setKey('opened_at', now()->timestamp);
        Cache::forget($this->getKey('half_open_attempts'));

        Log::warning("CircuitBreaker '{$this->name}' transitioned to OPEN state at {$this->getTimestamp()}.", $this->getStats());
    }

    private function transitionToHalfOpen(): void
    {
        $this->setKey('state', self::STATE_HALF_OPEN);
        $this->setKey('half_open_attempts', 0);

        Log::warning("CircuitBreaker '{$this->name}' transitioned to HALF_OPEN state at {$this->getTimestamp()}.", $this->getStats());
    }

    private function transitionToClosed(): void
    {
        Cache::forget($this->getKey('state'));
        Cache::forget($this->getKey('failures'));
        Cache::forget($this->getKey('opened_at'));
        Cache::forget($this->getKey('half_open_attempts'));

        Log::info("CircuitBreaker '{$this->name}' transitioned to CLOSED state at {$this->getTimestamp()}.", $this->getStats());
    }

    private function getKey(string $name): string
    {
        return "circuit_breaker:{$this->name}:$name";
    }

    private function setKey(string $name, mixed $value, ?\DateTime $time = null)
    {
        $time = $time ?? now()->addHours($this->cacheTtlHours);

        Cache::put($this->getKey($name), $value, $time);
    }

    private function getTimestamp(): string
    {
        return now()->format('Y-m-d H:i:s T');
    }

    private function sendAdminNotification(string $message): void
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

            Log::info('Circuit breaker notification sent to admin.', [
                'breaker' => $this->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send circuit breaker notification.', [
                'breaker' => $this->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildEmailContent(string $message, array $stats): string
    {
        $appName = config()->get('has-uploads.name', 'Laravel application');

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
