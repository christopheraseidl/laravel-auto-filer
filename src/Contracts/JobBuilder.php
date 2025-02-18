<?php

namespace christopheraseidl\HasUploads\Contracts;

interface JobBuilder
{
    public function __construct(JobBuilderValidator $validator);

    public function job(string $jobClass): self;

    public function setPayloadClass(): void;

    public function __call(string $method, array $arguments): self;

    public function resolveConstructorArguments(string $class): array;

    public function makePayload(): object;

    public function makeJob(object $payload): object;

    public function build(): self;
}
