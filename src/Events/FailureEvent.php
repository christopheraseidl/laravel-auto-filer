<?php

namespace christopheraseidl\ModelFiler\Events;

use christopheraseidl\ModelFiler\Payloads\Contracts\Payload;

/**
 * Broadcasts upload failure events with exception data.
 */
abstract class FailureEvent extends Event
{
    /**
     * Create a new failure event with optional exception details.
     */
    public function __construct(
        public readonly Payload $payload,
        public readonly ?\Throwable $exception = null
    ) {}
}
