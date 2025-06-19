<?php

namespace christopheraseidl\ModelFiler\Tests\TestClasses;

use christopheraseidl\ModelFiler\Jobs\Job;
use christopheraseidl\ModelFiler\Payloads\Contracts\Payload as PayloadContract;

class TestJobWithoutConstructor extends Job
{
    public function handle(): void {}

    public function getOperationType(): string
    {
        return 'test_job';
    }

    public function getPayload(): PayloadContract
    {
        return new PayloadContract;
    }

    public function uniqueId(): string
    {
        return md5($this->getOperationType());
    }
}
