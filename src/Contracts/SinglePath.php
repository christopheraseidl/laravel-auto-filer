<?php

namespace christopheraseidl\HasUploads\Contracts;

/**
 * Provides functionality for interacting with a path property.
 */
interface SinglePath
{
    public function getPath(): string;
}
