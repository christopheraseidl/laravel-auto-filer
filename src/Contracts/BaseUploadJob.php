<?php

namespace christopheraseidl\HasUploads\Contracts;

use Closure;
use DateTime;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

interface BaseUploadJob extends ConstructibleJob, ShouldBeUnique, ShouldQueue
{
    public static function make(?Payload $payload): ?static;

    public function handle(): void;

    public function handleJob(Closure $job): void;

    public function getOperationType(): string;

    public function uniqueId(): string;

    public function retryUntil(): DateTime;

    public function failed(Throwable $exception): void;

    public function middleware(): array;
}
