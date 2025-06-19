<?php

namespace christopheraseidl\ModelFiler\Traits;

trait HasPath
{
    public function getPath(): string
    {
        return $this->path;
    }
}
