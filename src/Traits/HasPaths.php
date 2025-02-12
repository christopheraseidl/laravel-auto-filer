<?php

namespace christopheraseidl\HasUploads\Traits;

trait HasPaths
{
    public function getPaths(): string
    {
        return $this->paths;
    }
}
