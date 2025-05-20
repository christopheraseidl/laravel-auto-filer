<?php

namespace christopheraseidl\HasUploads\Tests\Events;

use christopheraseidl\HasUploads\Events\Event;

/**
 * Tests structure of Event abstract class.
 *
 * @covers \christopheraseidl\HasUploads\Events\Event
 */
beforeEach(function () {
    $this->event = new \ReflectionClass(Event::class);
});

it('is an abstract class', function () {
    expect($this->event->isAbstract())->toBeTrue();
});

it('implements ShouldBroadcast interface', function () {
    expect($this->event->implementsInterface('Illuminate\Contracts\Broadcasting\ShouldBroadcast'))
        ->toBeTrue();
});

it('uses the Dispatchable and InteractsWithSockets traits', function () {
    $traits = $this->event->getTraitNames();

    expect($traits)->toContain(
        'Illuminate\Foundation\Events\Dispatchable',
        'Illuminate\Broadcasting\InteractsWithSockets'
    );
});

it('takes only a Payload argument in the constructor', function () {
    $constructor = $this->event->getConstructor();
    $parameters = $constructor->getParameters();
    $payload = $parameters[0];

    expect($parameters)->toHaveCount(1)
        ->and($payload->getName())->toBe('payload')
        ->and($payload->getType()->getName())->toBe('christopheraseidl\HasUploads\Payloads\Contracts\Payload');
});

it('has a broadcastOn() method with the correct return type', function () {
    $methodName = 'broadcastOn';
    $method = $this->event->getMethod($methodName);

    expect($this->event->hasMethod($methodName))->toBeTrue()
        ->and($method->getReturnType()->getName())->toBe('array');
});
