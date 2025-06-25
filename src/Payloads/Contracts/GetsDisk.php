<?php

namespace christopheraseidl\ModelFiler\Payloads\Contracts;

interface GetsDisk
{
    /**
     * Return storage disk name.
     */
    public function getDisk(): string;
}
