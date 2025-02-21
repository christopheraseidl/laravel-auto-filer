<?php

namespace christopheraseidl\HasUploads\Payloads;

use christopheraseidl\HasUploads\Payloads\Contracts\Payload as PayloadContract;
use ReflectionClass;

abstract class Payload implements PayloadContract
{
    public static function make(...$args): ?static
    {
        $class = static::class;
        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract()) {
            return null;
        }

        $constructor = $reflection->getConstructor();
        if (! $constructor) {
            return new $class;
        }

        $parameters = $constructor->getParameters();
        $namedArgs = [];
        foreach ($parameters as $index => $parameter) {
            if (isset($args[$index])) {
                $namedArgs[$parameter->getName()] = $args[$index];
            }
        }

        return new $class(...$namedArgs);
    }
}
