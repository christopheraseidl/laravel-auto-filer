<?php

namespace christopheraseidl\HasUploads\Traits;

trait HasDisk
{
    public function getDisk(): string
    {
        return $this->disk;
    }
}
