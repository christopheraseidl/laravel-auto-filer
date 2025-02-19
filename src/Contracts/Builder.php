<?php

namespace christopheraseidl\HasUploads\Contracts;

interface Builder
{
    public function __construct(BuilderValidator $validator);

    public function job(string $jobClass): self;

    public function setPayloadClass(): void;

    public function __call(string $method, array $arguments): self;

    public function resolveConstructorArguments(string $class): array;

    public function makePayload(): Payload;

    public function makeJob(object $payload): Job;

    public function build(): Job;
}
