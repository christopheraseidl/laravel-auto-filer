<?php

namespace christopheraseidl\ModelFiler\Contracts;

/**
 * Moves a file from the source path to the destination folder.
 */
interface FileMover
{
    /**
     * Move a file from the source path to the indicated destination folder.
     */
    public function move(string $sourcePath, string $destinationFolder): string;
}
