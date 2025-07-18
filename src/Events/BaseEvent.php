<?php

namespace christopheraseidl\AutoFiler\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Arr;

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
        return $this->getBroadcastChannels();
    }

    /**
     * Return an array of configured broadcast channels.
     */
    protected function getBroadcastChannels(): array
    {
        $channels = Arr::wrap(
            config('auto-filer.broadcast_channels')
        );

        return array_map(
            fn ($channel) => new PrivateChannel($channel),
            $channels
        );
    }
}
