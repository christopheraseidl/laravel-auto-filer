<?php

namespace christopheraseidl\HasUploads\Traits;

trait HasPath
{
    public function getPath(): string
    {
        return $this->path;
    }
}
