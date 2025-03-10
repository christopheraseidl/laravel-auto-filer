<?php

namespace christopheraseidl\HasUploads\Tests\TestClasses;

use christopheraseidl\HasUploads\Jobs\Job;
use christopheraseidl\HasUploads\Payloads\Contracts\Payload as PayloadContract;

class TestJob extends Job
{
    public PayloadContract $payload;

    public function handle(): void {}

    public function getOperationType(): string
    {
        return 'test_job';
    }

    public function getPayload(): PayloadContract
    {
        return $this->payload;
    }

    public function uniqueId(): string
    {
        return md5($this->getOperationType());
    }
}
