<?php

namespace christopheraseidl\HasUploads\Tests\Contracts;

use christopheraseidl\HasUploads\Contracts\SinglePath;

/**
 * Tests SinglePath interface structure.
 *
 * @covers \christopheraseidl\HasUploads\Contracts\SinglePath
 */
beforeEach(function () {
    $this->interface = new \ReflectionClass(SinglePath::class);
});

it('is an interface', function () {
    expect($this->interface->isInterface())->toBeTrue();
});

test('the getPath method has no parameters and returns a string', function () {
    $name = 'getPath';
    $method = $this->interface->getMethod($name);

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($method->getParameters())->toBeEmpty()
        ->and($method->getReturnType()->getName())->toBe('string');
});
