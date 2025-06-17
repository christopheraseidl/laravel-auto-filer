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
 * Abstract base class for file operation jobs with standardized configuration and error handling.
 *
 * Provides common functionality for all file operation jobs including:
 * - Dynamic job instantiation with payload validation
 * - Standardized job execution with event broadcasting
 * - Configurable connection and queue routing
 * - Built-in retry logic and failure handling
 * - Rate limiting and throttling middleware
 *
 * Child classes must implement the specific job logic while inheriting
 * the standardized infrastructure for reliability and monitoring.
 */
abstract class Job implements JobContract
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Factory method to create job instances with reflection-based validation.
     *
     * Validates that the job class is concrete and has the correct constructor
     * signature before instantiation. Returns null for abstract classes.
     *
     * @return static|null The job instance or null if class is abstract
     */
    public static function make(Payload $payload): ?static
    {
        $class = static::class;
        $reflection = new \ReflectionClass($class);

        if ($reflection->isAbstract()) {
            return null;
        }

        $constructor = $reflection->getConstructor();
        if (! $constructor) {
            return new $class;
        }

        return new $class($payload);
    }

    /**
     * Execute the job with standardized error handling and event broadcasting.
     *
     * Wraps the actual job logic with try-catch handling and broadcasts
     * success/failure events based on payload configuration. This provides
     * consistent behavior across all job types.
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
     * Set the retry timeout for failed jobs.
     *
     * Jobs will continue retrying for up to 5 minutes before being marked
     * as permanently failed.
     *
     * @return \DateTime The deadline for retry attempts
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5);
    }

    /**
     * Handle permanent job failure after all retries are exhausted.
     *
     * Logs the failure with job details for debugging and monitoring purposes.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job failed: {$this->getOperationType()}.", $this->getPayload()->toArray());
    }

    /**
     * Define middleware for job execution.
     *
     * Applies throttling for repeated exceptions and rate limiting to prevent
     * overwhelming the file system or external services.
     *
     * @return array Array of middleware instances
     */
    public function middleware(): array
    {
        return [
            new ThrottlesExceptions(10, 5),
            new RateLimited('uploads'),
        ];
    }

    /**
     * Get the queue connection for this job.
     *
     * Uses job-specific configuration if available, falling back to the
     * package default connection.
     *
     * @return string|null The connection name or null for default
     */
    public function getConnection(): ?string
    {
        $connection = $this->getJobOrDefaultConnection();

        return $connection;
    }

    /**
     * Get the queue name for this job.
     *
     * Uses job-specific configuration if available, falling back to the
     * package default queue.
     *
     * @return string|null The queue name or null for default
     */
    public function getQueue(): ?string
    {
        $queue = $this->getJobOrDefaultQueue();

        return $queue;
    }

    /**
     * Configure the job's connection and queue settings.
     *
     * Applies the resolved connection and queue configuration to the job instance.
     * Throws an exception if the configuration is invalid to fail fast.
     *
     * @throws \Exception When job configuration is invalid
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
     * Resolve the connection with job-specific override capability.
     *
     * @return string|null The resolved connection name
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
     * Resolve the queue with job-specific override capability.
     *
     * @return string|null The resolved queue name
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
     * Generate a configuration key name from the job class name.
     *
     * Converts the class name to snake_case for consistent configuration naming.
     *
     * @return string The snake_case job setting name
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
