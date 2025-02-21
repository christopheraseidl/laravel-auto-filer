<?php

namespace christopheraseidl\HasUploads\Events;

use christopheraseidl\HasUploads\Payloads\Contracts\Payload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class FileOperationStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly Payload $payload
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('file-operations.'.$this->payload->getKey()),
        ];
    }
}
