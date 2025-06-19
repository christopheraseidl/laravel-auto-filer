<?php

namespace christopheraseidl\ModelFiler\Jobs\Services;

use christopheraseidl\ModelFiler\Jobs\Contracts\Builder as BuilderContract;
use christopheraseidl\ModelFiler\Jobs\Contracts\BuilderValidator;
use christopheraseidl\ModelFiler\Jobs\Contracts\Job;
use christopheraseidl\ModelFiler\Payloads\Contracts\Payload;

/**
 * Fluently creates Job instances with validated payloads.
 *
 * Provides a convenient API for setting job properties dynamically and
 * constructing jobs with properly validated payload objects.
 */
class Builder implements BuilderContract
{
    protected array $properties = [];

    protected string $jobClass;

    public function __construct(
        protected BuilderValidator $validator
    ) {}

    /**
     * Magic method to dynamically set properties for the job payload.
     *
     * Allows fluent method chaining by capturing any method call as a property
     * name and storing the first argument as its value.
     *
     * @return self Returns the builder instance for method chaining
     */
    public function __call(string $method, array $arguments): self
    {
        $this->properties[$method] = $arguments[0];

        return $this;
    }

    /**
     * Set the job class to be instantiated.
     *
     * @return self Returns the builder instance for method chaining
     */
    public function job(string $jobClass): self
    {
        $this->jobClass = $jobClass;

        return $this;
    }

    /**
     * Create a job instance with the provided payload.
     *
     * @return Job The instantiated job with the given payload
     */
    public function makeJob(object $payload): Job
    {
        return app()->makeWith($this->jobClass, ['payload' => $payload]);
    }

    /**
     * Build the complete job with a validated payload constructed from stored properties.
     *
     * @return Job The fully constructed job ready for execution
     */
    public function build(): Job
    {
        $payload = $this->makePayload();

        return $this->makeJob($payload);
    }

    /**
     * Create a validated payload object from the accumulated properties.
     *
     * Uses the validator to determine the correct payload class and constructs
     * it with the properties that were set via the fluent interface.
     *
     * @return Payload The constructed and validated payload object
     */
    public function makePayload(): Payload
    {
        $payloadParameter = $this->validator->getValidPayloadParameter($this->jobClass);
        $payloadClass = $this->validator->getValidPayloadClassName($this->jobClass, $payloadParameter);

        return app()->makeWith($payloadClass, $this->properties);
    }
}
