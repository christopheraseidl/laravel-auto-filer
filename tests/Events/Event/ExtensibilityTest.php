<?php

namespace christopheraseidl\HasUploads\Tests\Events\Event;

use christopheraseidl\HasUploads\Events\Event;
use christopheraseidl\HasUploads\Tests\TestTraits\EventHelpers;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

uses(
    EventHelpers::class
);

/**
 * Tests Event class extensibility.
 *
 * @covers \christopheraseidl\HasUploads\Events\Event
 */
beforeEach(function () {
    $this->setHandler();
});

it('can be extended with concrete implementations', function () {
    expect($this->handler)->toBeInstanceOf(Event::class);
});

it('implements ShouldBroadcast interface', function () {
    expect($this->handler)->toBeInstanceOf(ShouldBroadcast::class);
});

it('sets payload property correctly from constructor', function () {
    $payload = Reflect::on($this->handler)->payload;

    expect($payload)->toBe($this->payloadTestValue);
});

it('returns correct broadcast channels', function () {
    $channels = $this->handler->broadcastOn();

    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(\Illuminate\Broadcasting\PrivateChannel::class);
});

it('can access parent method functionality', function () {
    $channels = $this->handler->broadcastOn();

    expect($channels)->toBeArray()
        ->and($channels)->not->toBeEmpty();
});
