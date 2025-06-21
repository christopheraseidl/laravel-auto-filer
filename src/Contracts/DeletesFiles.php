<?php

namespace christopheraseidl\ModelFiler\Contracts;

use christopheraseidl\ModelFiler\Jobs\Contracts\FileDeleter;

interface DeletesFiles
{
    /**
     * Get the file deleter instance.
     */
    public function getDeleter(): FileDeleter;
}
