<?php

namespace christopheraseidl\ModelFiler\ValueObjects;

use Illuminate\Support\Collection;

readonly class ChangeManifest
{
    public function __construct(
        public Collection $operations
    ) {}
}
