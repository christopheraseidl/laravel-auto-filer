<?php

namespace christopheraseidl\HasUploads\Contracts;

interface MultiPathPayload extends Payload
{
    public function getPaths(): ?array;
}
