<?php

namespace christopheraseidl\ModelFiler\Tests\TestClasses;

use christopheraseidl\ModelFiler\Payloads\Contracts\Payload as PayloadContract;
use christopheraseidl\ModelFiler\Payloads\Payload;

class TestJob extends TestJobWithoutConstructor
{
    public function __construct(
        public readonly Payload $payload
    ) {
        $this->config();
    }

    public function getPayload(): PayloadContract
    {
        return $this->payload;
    }
}
