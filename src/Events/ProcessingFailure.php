<?php

namespace christopheraseidl\ModelFiler\Events;

class ProcessingFailure extends BaseEvent
{
    public function __construct(
        \Throwable $e
    ) {}
}
