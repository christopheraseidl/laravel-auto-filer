<?php

namespace christopheraseidl\HasUploads\Contracts;

use Closure;
use DateTime;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

interface Job extends ShouldBeUnique, ShouldQueue
{
    /**
     * Create the job instance.
     */
    public static function make(Payload $payload): ?static;

    /**
     * Execute the job.
     */
    public function handle(): void;

    /**
     * Attempt to execute the job and broadcast completion or failure events.
     *
     * @throws \Throwable The original exception is caught internally but not re-thrown.
     */
    public function handleJob(Closure $job): void;

    /**
     * Get the operation type of the job.
     */
    public function getOperationType(): string;

    /**
     * Get the payload of the job.
     */
    public function getPayload(): Payload;

    /**
     * Get the unique ID of the job.
     */
    public function uniqueId(): string;

    /**
     * Get the timestamp indicating when the job should timeout.
     */
    public function retryUntil(): DateTime;

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void;

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array;
}
