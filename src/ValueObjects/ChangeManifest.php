<?php

namespace christopheraseidl\AutoFiler\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

readonly class ChangeManifest implements Arrayable
{
    public function __construct(
        public Collection $operations
    ) {}

    public function toArray(): array
    {
        return $this->operations->all();
    }
}
