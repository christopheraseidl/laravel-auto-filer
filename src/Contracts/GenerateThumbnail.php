<?php

namespace christopheraseidl\AutoFiler\Contracts;

interface GenerateThumbnail
{
    public function __invoke(string $imagePath, array $options = []): array;
}
