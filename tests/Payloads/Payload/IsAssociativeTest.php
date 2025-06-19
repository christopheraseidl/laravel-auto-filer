<?php

namespace christopheraseidl\ModelFiler\Tests\Payloads\Payload;

use christopheraseidl\ModelFiler\Tests\TestClasses\Payload\TestPayloadNoConstructor;
use christopheraseidl\Reflect\Reflect;

/**
 * Tests Payload isAssociative method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Payloads\Payload
 */
beforeEach(function () {
    $this->payload = new TestPayloadNoConstructor;
});

it('returns the expected value', function () {
    $associative = [
        'key1' => 'value1',
        'key2' => 'value2',
    ];
    $notAssociative = ['value3', 'value4'];

    expect(Reflect::on($this->payload)->isAssociative($associative))->toBeTrue();
    expect(Reflect::on($this->payload)->isAssociative($notAssociative))->toBeFalse();
});

it('returns false if empty array parameter is provided', function () {
    expect(Reflect::on($this->payload)->isAssociative([]))->toBeFalse();
});
