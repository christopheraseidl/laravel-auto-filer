<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\Contracts;

use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker;

/**
 * Tests ModelFileChangeTracker interface structure.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker
 */
beforeEach(function () {
    $this->interface = new \ReflectionClass(ModelFileChangeTracker::class);
});

it('is an interface', function () {
    expect($this->interface->isInterface())->toBeTrue();
});

it('has four methods', function () {
    $methods = $this->interface->getMethods();

    expect($methods)->toHaveCount(4);
});

test('all methods have the correct parameters and return value', function (string $methodName) {
    $method = $this->interface->getMethod($methodName);
    $parameters = $method->getParameters();
    $model = $parameters[0];
    $attribute = $parameters[1];

    expect($this->interface->hasMethod($methodName))->toBeTrue()
        ->and($parameters)->toHaveCount(2)
        ->and($model->getName())->toBe('model')
        ->and($model->getType()->getName())->toBe('Illuminate\Database\Eloquent\Model')
        ->and($attribute->getName())->toBe('attribute')
        ->and($attribute->getType()->getName())->toBe('string')
        ->and($method->getReturnType()->getName())->toBe('array');
})->with([
    ['getRemovedFiles'],
    ['getNewFiles'],
    ['getOriginalPaths'],
    ['getCurrentPaths'],
]);
