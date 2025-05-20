<?php

namespace christopheraseidl\HasUploads\Tests\Events;

use christopheraseidl\HasUploads\Events\FailureEvent;

/**
 * Tests structure of FailureEvent abstract class.
 *
 * @covers \christopheraseidl\HasUploads\Events\FailureEvent
 */
beforeEach(function () {
    $this->event = new \ReflectionClass(FailureEvent::class);
});

it('is an abstract class', function () {
    expect($this->event->isAbstract())->toBeTrue();
});

it('extends the Event class', function () {
    expect($this->event->getParentClass()->getName())
        ->toBe('christopheraseidl\HasUploads\Events\Event');
});

it('takes only Payload and Throwable arguments in the constructor', function () {
    $constructor = $this->event->getConstructor();
    $parameters = $constructor->getParameters();
    $payload = $parameters[0];
    $throwable = $parameters[1];

    expect($parameters)->toHaveCount(2)
        ->and($payload->getName())->toBe('payload')
        ->and($payload->getType()->getName())->toBe('christopheraseidl\HasUploads\Payloads\Contracts\Payload')
        ->and($throwable->getName())->toBe('exception')
        ->and($throwable->getType()->getName())->toBe('Throwable');
});
