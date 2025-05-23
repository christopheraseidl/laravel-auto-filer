<?php

namespace christopheraseidl\HasUploads\Tests\Payloads\Payload;

use christopheraseidl\HasUploads\Tests\TestClasses\Payload\TestPayloadNoConstructor;

beforeEach(function () {
    $this->payload = new TestPayloadNoConstructor;
    $reflection = new \ReflectionClass($this->payload);
    $this->method = $reflection->getMethod('isAssociative');
    $this->method->setAccessible(true);
});

it('returns the expected value', function () {
    $associative = [
        'key1' => 'value1',
        'key2' => 'value2',
    ];
    $notAssociative = ['value3', 'value4'];

    expect($this->method->invoke($this->payload, $associative))->toBeTrue()
        ->and($this->method->invoke($this->payload, $notAssociative))->toBeFalse();
});

it('returns false if empty array parameter is provided', function () {
    expect($this->method->invoke($this->payload, []))->toBeFalse();
});
