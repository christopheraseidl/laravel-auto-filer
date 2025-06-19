<?php

namespace christopheraseidl\ModelFiler\Payloads\Contracts;

interface MultiPath extends Payload
{
    public function getPaths(): ?array;
}
