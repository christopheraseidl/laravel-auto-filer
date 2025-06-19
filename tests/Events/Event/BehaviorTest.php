<?php

namespace christopheraseidl\ModelFiler\Tests\Events\Event;

use christopheraseidl\ModelFiler\Payloads\Contracts\Payload;
use christopheraseidl\ModelFiler\Tests\TestTraits\EventHelpers;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Broadcasting\PrivateChannel;

uses(
    EventHelpers::class
);

/**
 * Tests Event class method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Events\Event
 */
beforeEach(function () {
    config()->set('model-filer.broadcast_channel', 'test-channel');

    $this->setHandler();
});

it('broadcasts on configured private channel', function () {
    $channels = $this->handler->broadcastOn();

    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
    expect($channels[0]->name)->toBe('private-test-channel');
});

it('uses different channel when config changes', function (string $channelName) {
    config()->set('model-filer.broadcast_channel', $channelName);

    $channels = $this->handler->broadcastOn();

    expect($channels[0]->name)->toBe("private-{$channelName}");
})->with([
    'upload-events',
    'file-processing',
    'media-uploads',
]);

it('maintains payload accessibility', function () {
    $payload = Reflect::on($this->handler)->payload;

    expect($payload)->toBe($this->payloadTestValue)
        ->and($payload)->toBeInstanceOf(Payload::class);
});

it('returns array of channels', function () {
    $channels = $this->handler->broadcastOn();

    expect($channels)->toBeArray()
        ->and($channels)->toHaveCount(1);
});
