<?php

namespace christopheraseidl\ModelFiler\Contracts;

use christopheraseidl\ModelFiler\Jobs\Contracts\FileMover;

interface MovesFiles
{
    /**
     * Get the file mover instance.
     */
    public function getMover(): FileMover;
}
