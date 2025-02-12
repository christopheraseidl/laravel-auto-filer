<?php

namespace christopheraseidl\HasUploads\Traits;

trait GetsClassBaseName
{
    private string $classBaseName;

    protected function getClassBaseName(object $object): string
    {
        return $this->classBaseName ??= class_basename($object);
    }
}
