<?php

namespace christopheraseidl\HasUploads\Contracts;

interface ConstructiblePayload
{
    /**
     * Construct a payload instance from raw input data.
     * Returns null if the payload cannot be constructed from the given data.
     */
    public static function make(...$args): ?static;
}
