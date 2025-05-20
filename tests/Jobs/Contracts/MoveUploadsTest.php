<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Contracts;

use christopheraseidl\HasUploads\Jobs\Contracts\MoveUploads;

/**
 * Tests MoveUploads interface structure.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Contracts\MoveUploads
 */
beforeEach(function () {
    $this->interface = new \ReflectionClass(MoveUploads::class);
});

it('is an interface', function () {
    expect($this->interface->isInterface())->toBeTrue();
});

it('has three methods', function () {
    // Filter methods for only those declared by MoveUploads.
    $methods = array_filter(
        $this->interface->getMethods(),
        function ($method) {
            return $method->getDeclaringClass()->getName() === $this->interface->getName();
        }
    );

    expect($methods)->toHaveCount(3);
});

it('extends the Job interface', function () {
    $interfaces = $this->interface->getInterfaceNames();

    expect($interfaces)->toContain(
        'christopheraseidl\HasUploads\Jobs\Contracts\Job',
    );
});

test('the constructor takes a MoveUploadsPayload argument', function () {
    $constructor = $this->interface->getConstructor();
    $parameters = $constructor->getParameters();
    $validator = $parameters[0];

    expect($parameters)->toHaveCount(1)
        ->and($validator->getName())->toBe('payload')
        ->and($validator->getType()->getName())->toBe('christopheraseidl\HasUploads\Payloads\Contracts\MoveUploads');
});

test('the attemptMove() method takes the correct parameters and returns a string', function () {
    $name = 'attemptMove';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $disk = $parameters[0];
    $oldPath = $parameters[1];
    $newDir = $parameters[2];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(3)
        ->and($disk->getName())->toBe('disk')
        ->and($disk->getType()->getName())->toBe('string')
        ->and($oldPath->getName())->toBe('oldPath')
        ->and($oldPath->getType()->getName())->toBe('string')
        ->and($newDir->getName())->toBe('newDir')
        ->and($newDir->getType()->getName())->toBe('string')
        ->and($method->getReturnType()->getName())->toBe('string');
});

test('the normalizeAttributeValue() method takes the correct parameters and returns string|array|null', function () {
    // Get the method.
    $name = 'normalizeAttributeValue';
    $method = $this->interface->getMethod($name);
    // Get the parameters.
    $parameters = $method->getParameters();
    $model = $parameters[0];
    $attribute = $parameters[1];
    // Get the return types.
    $returnTypes = array_map(function ($returnType) {
        return $returnType->getName();
    }, $method->getReturnType()->getTypes());

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(2)
        ->and($model->getName())->toBe('model')
        ->and($model->getType()->getName())->toBe('Illuminate\Database\Eloquent\Model')
        ->and($attribute->getName())->toBe('attribute')
        ->and($attribute->getType()->getName())->toBe('string')
        ->and($returnTypes)->toContain(
            'array',
            'string',
            'null',
        );
});
