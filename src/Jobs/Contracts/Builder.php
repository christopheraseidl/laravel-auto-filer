<?php

namespace christopheraseidl\HasUploads\Jobs\Contracts;

use christopheraseidl\HasUploads\Payloads\Contracts\Payload;

interface Builder
{
    public function __construct(BuilderValidator $validator);

    public function job(string $jobClass): self;

    public function __call(string $method, array $arguments): self;

    public function makePayload(): Payload;

    public function makeJob(Payload $payload): Job;

    public function build(): Job;
}
