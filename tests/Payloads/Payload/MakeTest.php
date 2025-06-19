<?php

namespace christopheraseidl\ModelFiler\Tests\Payloads;

use christopheraseidl\ModelFiler\Payloads\Contracts\Payload as PayloadContract;
use christopheraseidl\ModelFiler\Payloads\Payload;
use christopheraseidl\ModelFiler\Tests\TestClasses\Payload\TestPayload;
use christopheraseidl\ModelFiler\Tests\TestClasses\Payload\TestPayloadNoConstructor;

/**
 * Tests Payload make method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Payloads\Payload
 */
beforeEach(function () {
    $this->payloadNoConstructor = new TestPayloadNoConstructor;
    $this->payloadWithConstructor = new TestPayload(
        'test value',
        null,
        [
            0 => 'array value 1',
            1 => 'array value 2',
        ]
    );
});

it('makes a new Payload instance from provided parameters', function () {
    $instance = $this->payloadWithConstructor::make('new value', 'second param');

    expect($instance)->toBeInstanceOf(PayloadContract::class);
    expect($instance->required)->toBe('new value');
    expect($instance->paramOne)->toBe('second param');
});

it('returns null if the calling class is abstract', function () {
    $return = Payload::make('will return null', 1);

    expect($return)->toBeNull();
});

it('returns a new instance of the class is there is no constructor', function () {
    $instance = $this->payloadNoConstructor::make('new value', 100);

    expect($instance)->toBeInstanceOf(TestPayloadNoConstructor::class);
});

it('throws an exception if a required parameter is missing', function () {
    $instance = $this->payloadWithConstructor::make();
})->throws(\InvalidArgumentException::class);
