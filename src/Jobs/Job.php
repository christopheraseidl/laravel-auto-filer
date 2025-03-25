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
}
