<?php

namespace christopheraseidl\ModelFiler\Tests\TestClasses;

use christopheraseidl\ModelFiler\Payloads\Payload;

class TestJobPayload extends Payload
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return true;
    }

    public function getKey(): string
    {
        return 'payload_key';
    }

    public function getDisk(): string
    {
        return 'public';
    }

    public function toArray(): array
    {
        return [
            'key' => 'value',
        ];
    }
}
