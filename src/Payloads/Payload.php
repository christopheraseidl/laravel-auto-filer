<?php

namespace christopheraseidl\HasUploads\Payloads;

use christopheraseidl\HasUploads\Payloads\Contracts\Payload as PayloadContract;

/**
 * Creates payload instances with dynamic parameter resolution.
 */
abstract class Payload implements PayloadContract
{
    /**
     * Create a new payload instance using reflection-based parameter mapping.
     */
    public static function make(...$args): ?static
    {
        $class = static::class;
        $reflection = new \ReflectionClass($class);

        if ($reflection->isAbstract()) {
            return null;
        }

        $constructor = $reflection->getConstructor();
        if (! $constructor) {
            return new $class;
        }

        $parameters = $constructor->getParameters();
        $parsedArgs = [];
        foreach ($parameters as $index => $parameter) {
            $paramName = $parameter->getName();
            $key = static::isAssociative($args) ? $paramName : $index;
            if (array_key_exists($paramName, $args) || array_key_exists($index, $args)) {
                // Parameter exists in provided arguments
                $parsedArgs[$key] = $args[$key];
            } elseif ($parameter->isDefaultValueAvailable()) {
                // Use constructor default value
                $parsedArgs[$paramName] = $parameter->getDefaultValue();
            } elseif ($parameter->allowsNull()) {
                // Set nullable parameter to null
                $parsedArgs[$paramName] = null;
            } else {
                // Required parameter missing
                throw new \InvalidArgumentException(
                    "Missing required parameter $parameter for class $class"
                );
            }
        }

        return new $class(...$parsedArgs);
    }

    /**
     * Check if array has string keys rather than sequential numeric keys.
     */
    protected static function isAssociative(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
