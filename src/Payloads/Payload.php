<?php

namespace christopheraseidl\HasUploads\Payloads;

use christopheraseidl\HasUploads\Payloads\Contracts\Payload as PayloadContract;

abstract class Payload implements PayloadContract
{
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
                // Parameter exists in $args.
                $parsedArgs[$key] = $args[$key];
            } elseif ($parameter->isDefaultValueAvailable()) {
                // Default value found for parameter.
                $parsedArgs[$paramName] = $parameter->getDefaultValue();
            } elseif ($parameter->allowsNull()) {
                // No default value found, but is nullable.
                $parsedArgs[$paramName] = null;
            } else {
                // Parameter required but not provided.
                throw new \InvalidArgumentException(
                    "Missing required parameter $parameter for class $class"
                );
            }
        }

        return new $class(...$parsedArgs);
    }

    protected static function isAssociative(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
