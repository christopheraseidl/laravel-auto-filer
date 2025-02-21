<?php

namespace christopheraseidl\HasUploads\Jobs\Services;

use christopheraseidl\HasUploads\Jobs\Contracts\Builder as BuilderContract;
use christopheraseidl\HasUploads\Jobs\Contracts\BuilderValidator;
use christopheraseidl\HasUploads\Jobs\Contracts\Job;
use christopheraseidl\HasUploads\Payloads\Contracts\Payload;
use ReflectionClass;

class Builder implements BuilderContract
{
    protected array $properties = [];

    protected string $jobClass;

    protected string $payloadClass;

    public function __construct(
        protected BuilderValidator $validator
    ) {}

    public function job(string $jobClass): self
    {
        $this->jobClass = $jobClass;
        $this->setPayloadClass();

        return $this;
    }

    public function setPayloadClass(): void
    {
        $payloadParameter = $this->validator->getValidPayloadParameter($this->jobClass);
        $this->payloadClass = $this->validator->getValidPayloadClassName($this->jobClass, $payloadParameter);
    }

    public function __call(string $method, array $arguments): self
    {
        $this->properties[$method] = $arguments[0];

        return $this;
    }

    public function resolveConstructorArguments(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return [];
        }

        $args = [];

        foreach ($constructor->getParameters() as $parameter) {
            $args[] = $this->properties[$parameter->getName()] ?? null;
        }

        return $args;
    }

    public function makePayload(): Payload
    {
        $args = $this->resolveConstructorArguments($this->payloadClass);

        return $this->payloadClass::make(...$args);
    }

    public function makeJob(object $payload): Job
    {
        return $this->jobClass::make($payload);
    }

    public function build(): Job
    {
        $this->validator->validatePropertiesExistForPayload($this->properties, $this->payloadClass);
        $payload = $this->makePayload();

        return $this->makeJob($payload);
    }
}
