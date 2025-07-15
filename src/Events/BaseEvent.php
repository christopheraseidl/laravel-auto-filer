<?php

namespace christopheraseidl\AutoFiler\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Base class for broadcasting events.
 */
abstract class BaseEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(config('auto-filer.broadcast_channel', 'default')),
        ];
    }
}
