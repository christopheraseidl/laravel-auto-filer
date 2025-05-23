<?php

namespace christopheraseidl\HasUploads\Tests\TestClasses\Payload;

class TestPayload extends TestPayloadNoConstructor
{
    public function __construct(
        public string $required,
        public ?string $paramOne = '',
        public ?array $paramTwo = []
    ) {}
}
