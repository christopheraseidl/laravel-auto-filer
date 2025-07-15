<?php

namespace christopheraseidl\AutoFiler\Events;

class ProcessingFailure extends BaseEvent
{
    public function __construct(
        public readonly \Throwable $e
    ) {}
}
