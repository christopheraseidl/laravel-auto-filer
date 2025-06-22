<?php

namespace christopheraseidl\ModelFiler\Contracts;

/**
 * Provides caching capabilities for implementing classes.
 */
interface Cacheable
{
    /**
     * Resolve intercoming method calls as Cache facade methods.
     */
    public function __call(string $method, array $arguments): self;

    /**
     * Validate method existence on the Laravel Cache facade.
     */
    public function validateMacro(string $method, array $arguments): void;
}
