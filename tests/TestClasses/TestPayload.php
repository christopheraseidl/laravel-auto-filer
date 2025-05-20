<?php

namespace christopheraseidl\HasUploads\Tests\TestClasses;

use christopheraseidl\HasUploads\Payloads\Payload;

class TestPayload extends Payload
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
