<?php

namespace christopheraseidl\HasUploads\Payloads\Contracts;

interface MultiPath extends Payload
{
    public function getPaths(): ?array;
}
