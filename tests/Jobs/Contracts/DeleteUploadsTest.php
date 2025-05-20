<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Contracts;

use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploads;

/**
 * Tests DeleteUploads interface structure.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploads
 */
beforeEach(function () {
    $this->interface = new \ReflectionClass(DeleteUploads::class);
});

it('is an interface', function () {
    expect($this->interface->isInterface())->toBeTrue();
});

it('has one method', function () {
    // Filter methods for only those declared by DeleteUploads.
    $methods = array_filter(
        $this->interface->getMethods(),
        function ($method) {
            return $method->getDeclaringClass()->getName() === $this->interface->getName();
        }
    );

    expect($methods)->toHaveCount(1);
});

test('the constructor takes a DeleteUploadsPayload argument', function () {
    $constructor = $this->interface->getConstructor();
    $parameters = $constructor->getParameters();
    $validator = $parameters[0];

    expect($parameters)->toHaveCount(1)
        ->and($validator->getName())->toBe('payload')
        ->and($validator->getType()->getName())->toBe('christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploads');
});
