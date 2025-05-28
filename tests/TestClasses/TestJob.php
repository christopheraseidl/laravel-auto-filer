<?php

namespace christopheraseidl\HasUploads\Tests\TestClasses;

use christopheraseidl\HasUploads\Payloads\Contracts\Payload as PayloadContract;
use christopheraseidl\HasUploads\Payloads\Payload;

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
