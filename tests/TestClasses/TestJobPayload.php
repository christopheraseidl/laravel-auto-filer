<?php

namespace christopheraseidl\HasUploads\Tests\TestClasses;

use christopheraseidl\HasUploads\Payloads\Payload;

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
