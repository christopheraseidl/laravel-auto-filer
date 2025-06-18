<?php

namespace christopheraseidl\HasUploads\Jobs\Validators;

use christopheraseidl\HasUploads\Jobs\Contracts\BuilderValidator as BuilderValidatorContract;

/**
 * Validates job builder operations using reflection to ensure proper job structure.
 */
class BuilderValidator implements BuilderValidatorContract
{
    /**
     * Get the single constructor parameter from a job class.
     */
    public function getValidPayloadParameter(string $jobClass): \ReflectionParameter
    {
        $reflection = new \ReflectionClass($jobClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            throw new \InvalidArgumentException("{$jobClass} must have a constructor.");
        }

        $parameters = $constructor->getParameters();

        if (empty($parameters) || count($parameters) != 1) {
            throw new \InvalidArgumentException("{$jobClass} constructor must have one parameter.");
        }

        return $parameters[0];
    }

    /**
     * Extract the class name from a constructor parameter's type hint.
     */
    public function getValidPayloadClassName(string $jobClass, \ReflectionParameter $parameter): string
    {
        $parameterType = $parameter->getType();

        if (! ($parameterType instanceof \ReflectionNamedType) || $parameterType->isBuiltin()) {
            throw new \InvalidArgumentException("Parameter of {$jobClass} constructor must be a class type.");
        }

        return $parameterType->getName();
    }

    /**
     * Validate that all required properties are provided for payload construction.
     */
    public function validatePropertiesExistForPayload(array $properties, string $payloadClass): void
    {
        $reflection = new \ReflectionClass($payloadClass);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return;
        }

        $missingProperties = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (! array_key_exists($name, $properties) && ! $parameter->isOptional()) {
                $missingProperties[] = $name;
            }
        }

        if (! empty($missingProperties)) {
            throw new \InvalidArgumentException(
                "Missing required properties for {$payloadClass}: ".implode(', ', $missingProperties)
            );
        }
    }
}
