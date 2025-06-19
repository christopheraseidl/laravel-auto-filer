<?php

namespace christopheraseidl\ModelFiler\Tests\TestClasses\Payload;

use christopheraseidl\ModelFiler\Payloads\Payload;

class TestPayloadNoConstructor extends Payload
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return true;
    }

    public function getKey(): string
    {
        return 'test_payload_key';
    }

    public function getDisk(): string
    {
        return 'test_payload_disk';
    }

    public function toArray()
    {
        return [
            'key' => 'value',
        ];
    }
}
