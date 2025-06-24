<?php

namespace christopheraseidl\ModelFiler\Traits;

use christopheraseidl\ModelFiler\Jobs\Contracts\FileMover;

trait HasFileMover
{
    protected FileMover $mover;

    /**
     * Get the file deleter instance.
     */
    public function getMover(): FileMover
    {
        return $this->mover;
    }
}
