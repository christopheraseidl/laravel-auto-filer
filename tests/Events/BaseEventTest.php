<?php

namespace christopheraseidl\AutoFiler\Tests\Events;

use christopheraseidl\AutoFiler\Events\BaseEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Arr;

beforeEach(function () {
    $this->event = new class extends BaseEvent {};
});

it('broadcasts on the configured channel(s)', function (string|array $channels) {
    config()->set('auto-filer.broadcast_channels', $channels);

    $channels = Arr::wrap($channels);
    $broadcasts = [];

    foreach ($channels as $channel) {
        $broadcasts[] = new PrivateChannel($channel);
    }

    $result = $this->event->broadcastOn();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(count($broadcasts));
    expect($result)->toEqual($broadcasts);
})->with([
    'one channel' => 'app-channel',
    'multiple channels' => ['app-channel1', 'app-channel2', 'app-channel3'],
]);

it('does not broadcast when no channel is configured', function () {
    config()->set('auto-filer.broadcast_channels', null);

    $result = $this->event->broadcastOn();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});
