<?php

namespace christopheraseidl\HasUploads\Events;

use christopheraseidl\HasUploads\Payloads\Contracts\Payload;

abstract class FailureEvent extends Event
{
    public function __construct(
        public readonly Payload $payload,
        public readonly ?\Throwable $exception = null
    ) {}
}
