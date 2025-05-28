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

abstract class Job implements JobContract
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Job failed: {$this->getOperationType()}.", $this->getPayload()->toArray());
    }

    public function middleware(): array
    {
        return [
            new ThrottlesExceptions(10, 5),
            new RateLimited('uploads'),
        ];
    }

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

    public function getConnection(): ?string
    {
        $connection = $this->getJobOrDefaultConnection();

        return $connection;
    }

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

    public function getQueue(): ?string
    {
        $queue = $this->getJobOrDefaultQueue();

        return $queue;
    }

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

    private function getJobSettingName(): string
    {
        $name = get_class($this);
        $name = explode('\\', $name);
        $name = end($name);
        $name = mb_strtolower(
            Str::snake($name)
        );

        return $name;
    }
}
