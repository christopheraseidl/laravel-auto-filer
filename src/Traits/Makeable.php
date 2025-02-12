<?php

namespace christopheraseidl\HasUploads\Traits;

trait Makeable
{
    public static function make($payload): ?static
    {
        return $payload ? new static($payload) : null;
    }
}
