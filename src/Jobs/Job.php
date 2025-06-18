<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use christopheraseidl\HasUploads\Jobs\Contracts\Job as JobContract;
use christopheraseidl\HasUploads\Payloads\Contracts\Payload;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Provides standardized configuration and error handling for file operation jobs.
 */
abstract class Job implements JobContract
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Provides standardized configuration and error handling for file operation jobs.
     */
    public static function make(Payload $payload): ?static
    {
        $class = static::class;
        $reflection = new \ReflectionClass($class);

        if ($reflection->isAbstract()) {
            return null; // Cannot instantiate abstract classes
        }

        $constructor = $reflection->getConstructor();
        if (! $constructor) {
            return new $class;
        }

        return new $class($payload);
    }

    /**
     * Execute job with standardized error handling and event broadcasting.
     */
    public function handleJob(\Closure $job): void
    {
        try {
            $job();

            if ($this->getPayload()->shouldBroadcastIndividualEvents()) {
                broadcast(new FileOperationCompleted(
                    $this->getPayload()
                ));
            }
        } catch (\Throwable $e) {
            if ($this->getPayload()->shouldBroadcastIndividualEvents()) {
                broadcast(new FileOperationFailed(
                    $this->getPayload(),
                    $e
                ));
            }
        }
    }

    /**
     * Set retry timeout for failed jobs to 5 minutes.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5);
    }

    /**
     * Handle permanent job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job failed: {$this->getOperationType()}.", $this->getPayload()->toArray());
    }

    /**
     * Define middleware for job execution with throttling and rate limiting.
     */
    public function middleware(): array
    {
        // By default, allow 10 exceptions in 5 minutes
        $maxAttempts = config('has-uploads.throttle_exception_attempts', 10);
        $period = config('has-uploads.throttle_exception_period', 5);

        return [
            new ThrottlesExceptions($maxAttempts, $period),
            new RateLimited('uploads'),
        ];
    }

    /**
     * Get queue connection using job-specific configuration or package default.
     */
    public function getConnection(): ?string
    {
        $connection = $this->getJobOrDefaultConnection();

        return $connection;
    }

    /**
     * Get queue name using job-specific configuration or package default.
     */
    public function getQueue(): ?string
    {
        $queue = $this->getJobOrDefaultQueue();

        return $queue;
    }

    /**
     * Configure job's connection and queue settings.
     */
    protected function config()
    {
        try {
            $connection = $this->getConnection();
            $queue = $this->getQueue();

            if ($connection) {
                $this->onConnection($connection);
            }

            if ($queue) {
                $this->onQueue($queue);
            }
        } catch (\Exception $e) {
            $message = 'The job configuration is invalid';
            Log::error($message, [
                'job' => static::class,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception($message, 0, $e);
        }
    }

    /**
     * Resolve connection with job-specific override capability.
     */
    protected function getJobOrDefaultConnection(): ?string
    {
        $defaultConnection = $this->getDefaultConnection();
        $jobConnection = $this->getJobConnection();

        return $jobConnection ?? $defaultConnection;
    }

    protected function getDefaultConnection(): ?string
    {
        return config('has-uploads.default_connection');
    }

    protected function getJobConnection(): ?string
    {
        $name = $this->getJobSettingName();
        $name = "{$name}_connection";

        return config("has-uploads.{$name}");
    }

    /**
     * Resolve queue with job-specific override capability.
     */
    protected function getJobOrDefaultQueue(): ?string
    {
        $defaultQueue = $this->getDefaultQueue();
        $jobQueue = $this->getJobQueue();

        return $jobQueue ?? $defaultQueue;
    }

    protected function getDefaultQueue(): ?string
    {
        return config('has-uploads.default_queue');
    }

    protected function getJobQueue(): ?string
    {
        $name = $this->getJobSettingName();
        $name = "{$name}_queue";

        return config("has-uploads.{$name}");
    }

    /**
     * Generate configuration key name from job class name.
     */
    private function getJobSettingName(): string
    {
        $name = static::class;
        $name = explode('\\', $name);
        $name = end($name);
        $name = mb_strtolower(
            Str::snake($name)
        );

        return $name;
    }
}
