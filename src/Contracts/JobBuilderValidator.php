<?php

namespace christopheraseidl\HasUploads\Contracts;

use ReflectionParameter;

interface JobBuilderValidator
{
    /**
     * Get a valid payload parameter from the job constructor.
     *
     *
     * @throws \InvalidArgumentException
     */
    public function getValidPayloadParameter(string $jobClass): ReflectionParameter;

    /**
     * Get a valid payload class name from a parameter.
     *
     *
     * @throws \InvalidArgumentException
     */
    public function getValidPayloadClassName(string $jobClass, ReflectionParameter $parameter): string;

    /**
     * Validate that all required properties exist for constructing the payload.
     *
     *
     * @throws \InvalidArgumentException
     */
    public function validatePropertiesExistForPayload(array $properties, string $payloadClass): void;
}
