<?php

namespace christopheraseidl\ModelFiler\Traits;

use christopheraseidl\ModelFiler\Jobs\Contracts\FileDeleter;

trait HasFileDeleter
{
    protected FileDeleter $deleter;

    /**
     * Get the file deleter instance.
     */
    public function getDeleter(): FileDeleter
    {
        return $this->deleter;
    }
}
