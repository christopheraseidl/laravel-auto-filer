<?php

namespace christopheraseidl\HasUploads\Events;

use christopheraseidl\HasUploads\Payloads\Contracts\Payload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Broadcasts upload events with payload data.
 */
abstract class Event implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly Payload $payload
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(config('has-uploads.broadcast_channel')),
        ];
    }
}
