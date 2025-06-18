<?php

namespace christopheraseidl\HasUploads\Jobs\Contracts;

use christopheraseidl\HasUploads\Payloads\Contracts\Payload;

/**
 * Builds jobs with fluent interface for configuration.
 */
interface Builder
{
    public function __construct(BuilderValidator $validator);

    /**
     * Set the job class to instantiate.
     */
    public function job(string $jobClass): self;

    /**
     * Handle dynamic method calls for configuration.
     */
    public function __call(string $method, array $arguments): self;

    /**
     * Create payload from current configuration.
     */
    public function makePayload(): Payload;

    /**
     * Instantiate job with given payload.
     */
    public function makeJob(Payload $payload): Job;

    /**
     * Build and return the configured job.
     */
    public function build(): Job;
}
