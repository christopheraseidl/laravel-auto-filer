<?php

namespace christopheraseidl\ModelFiler\Traits;

trait HasDisk
{
    public function getDisk(): string
    {
        return $this->disk;
    }
}
