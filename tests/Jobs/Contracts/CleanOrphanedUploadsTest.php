<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Contracts;

use christopheraseidl\HasUploads\Jobs\Contracts\CleanOrphanedUploads;

/**
 * Tests CleanOrphanedUploads interface structure.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Contracts\CleanOrphanedUploads
 */
beforeEach(function () {
    $this->interface = new \ReflectionClass(CleanOrphanedUploads::class);
});

it('is an interface', function () {
    expect($this->interface->isInterface())->toBeTrue();
});

it('has two methods', function () {
    // Filter methods for only those declared by CleanOrphanedUploads.
    $methods = array_filter(
        $this->interface->getMethods(),
        function ($method) {
            return $method->getDeclaringClass()->getName() === $this->interface->getName();
        }
    );

    expect($methods)->toHaveCount(2);
});

test('the constructor takes a CleanOrphanedUploadsPayload argument', function () {
    $constructor = $this->interface->getConstructor();
    $parameters = $constructor->getParameters();
    $validator = $parameters[0];

    expect($parameters)->toHaveCount(1)
        ->and($validator->getName())->toBe('payload')
        ->and($validator->getType()->getName())->toBe('christopheraseidl\HasUploads\Payloads\Contracts\CleanOrphanedUploads');
});

test('the getLastModified() method takes a file string parameter and returns DateTimeInterface', function () {
    $name = 'getLastModified';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $file = $parameters[0];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(1)
        ->and($file->getName())->toBe('file')
        ->and($file->getType()->getName())->toBe('string')
        ->and($method->getReturnType()->getName())->toBe('DateTimeInterface');
});
