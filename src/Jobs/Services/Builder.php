<?php

namespace christopheraseidl\HasUploads\Jobs\Services;

use christopheraseidl\HasUploads\Jobs\Contracts\Builder as BuilderContract;
use christopheraseidl\HasUploads\Jobs\Contracts\BuilderValidator;
use christopheraseidl\HasUploads\Jobs\Contracts\Job;
use christopheraseidl\HasUploads\Payloads\Contracts\Payload;

class Builder implements BuilderContract
{
    protected array $properties = [];

    protected string $jobClass;

    public function __construct(
        protected BuilderValidator $validator
    ) {}

    public function __call(string $method, array $arguments): self
    {
        $this->properties[$method] = $arguments[0];

        return $this;
    }

    public function job(string $jobClass): self
    {
        $this->jobClass = $jobClass;

        return $this;
    }

    public function makeJob(object $payload): Job
    {
        return app()->makeWith($this->jobClass, ['payload' => $payload]);
    }

    public function build(): Job
    {
        $payload = $this->makePayload();

        return $this->makeJob($payload);
    }

    public function makePayload(): Payload
    {
        $payloadParameter = $this->validator->getValidPayloadParameter($this->jobClass);
        $payloadClass = $this->validator->getValidPayloadClassName($this->jobClass, $payloadParameter);

        return app()->makeWith($payloadClass, $this->properties);
    }
}
