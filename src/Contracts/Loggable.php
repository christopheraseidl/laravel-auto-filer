<?php

namespace christopheraseidl\ModelFiler\Contracts;

/**
 * Provides logging capabilities for implementing classes.
 */
interface Loggable
{
    /**
     * Resolve intercoming method calls as Log facade methods.
     */
    public function __call(string $method, array $arguments): self;

    /**
     * Validate method existence on the Laravel Log facade.
     */
    public function validateMacro(string $method, array $arguments): void;
}
