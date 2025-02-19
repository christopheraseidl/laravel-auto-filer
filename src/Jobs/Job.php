<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Contracts\Job as JobContract;
use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use Closure;
use DateTime;
use Illuminate\Bus\Batchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class Job implements JobContract
{
    use Batchable, Queueable;

    public function handleJob(Closure $job): void
    {
        try {
            $job();

            if ($this->getPayload()->shouldBroadcastIndividualEvents()) {
                broadcast(new FileOperationCompleted(
                    $this->getPayload()
                ));
            }
        } catch (Throwable $e) {
            if ($this->getPayload()->shouldBroadcastIndividualEvents()) {
                broadcast(new FileOperationFailed(
                    $this->getPayload(),
                    $e
                ));
            }
        }
    }

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(5);
    }

    public function failed(Throwable $exception): void
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
