<?php

namespace christopheraseidl\HasUploads\Jobs\Validators;

use christopheraseidl\HasUploads\Jobs\Contracts\BuilderValidator as BuilderValidatorContract;

/**
 * Validator for job builder operations using reflection to ensure proper job structure.
 *
 * Validates that job classes have the correct constructor signature and that
 * payload classes can be properly instantiated with the provided properties.
 */
class BuilderValidator implements BuilderValidatorContract
{
    /**
     * Get the single constructor parameter from a job class.
     *
     * Validates that the job class has exactly one constructor parameter,
     * which should be the payload object.
     *
     * @return \ReflectionParameter The validated constructor parameter
     *
     * @throws \InvalidArgumentException When job has no constructor or wrong parameter count
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
     *
     * Ensures the parameter is typed as a class (not a builtin type) and
     * returns the fully qualified class name for payload instantiation.
     *
     * @return string The fully qualified payload class name
     *
     * @throws \InvalidArgumentException When parameter is not a valid class type
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
     *
     * Checks the payload class constructor to ensure all non-optional parameters
     * have corresponding values in the properties array.
     *
     * @throws \InvalidArgumentException When required properties are missing
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
