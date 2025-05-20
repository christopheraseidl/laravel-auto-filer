<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\Contracts;

use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager;

/**
 * Tests BatchManager interface structure.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\Contracts\BatchManager
 */
beforeEach(function () {
    $this->interface = new \ReflectionClass(BatchManager::class);
});

it('is an interface', function () {
    expect($this->interface->isInterface())->toBeTrue();
});

it('has three methods', function () {
    $methods = $this->interface->getMethods();

    expect($methods)->toHaveCount(3);
});

test('the dispatch() method has the correct parameters and return value', function () {
    $name = 'dispatch';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $jobs = $parameters[0];
    $model = $parameters[1];
    $disk = $parameters[2];
    $description = $parameters[3];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(4)
        ->and($jobs->getName())->toBe('jobs')
        ->and($jobs->getType()->getName())->toBe('array')
        ->and($model->getName())->toBe('model')
        ->and($model->getType()->getName())->toBe('Illuminate\Database\Eloquent\Model')
        ->and($disk->getName())->toBe('disk')
        ->and($disk->getType()->getName())->toBe('string')
        ->and($description->getName())->toBe('description')
        ->and($description->getType()->getName())->toBe('string')
        ->and($method->getReturnType()->getName())->toBe('void');
});

test('the handleSuccess() method has the correct parameters and return value', function () {
    $name = 'handleSuccess';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $batch = $parameters[0];
    $model = $parameters[1];
    $disk = $parameters[2];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(3)
        ->and($batch->getName())->toBe('batch')
        ->and($batch->getType()->getName())->toBe('Illuminate\Bus\Batch')
        ->and($model->getName())->toBe('model')
        ->and($model->getType()->getName())->toBe('Illuminate\Database\Eloquent\Model')
        ->and($disk->getName())->toBe('disk')
        ->and($disk->getType()->getName())->toBe('string')
        ->and($method->getReturnType()->getName())->toBe('void');
});

test('the handleFailure() method has the correct parameters and return value', function () {
    $name = 'handleFailure';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $batch = $parameters[0];
    $model = $parameters[1];
    $disk = $parameters[2];
    $throwable = $parameters[3];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(4)
        ->and($batch->getName())->toBe('batch')
        ->and($batch->getType()->getName())->toBe('Illuminate\Bus\Batch')
        ->and($model->getName())->toBe('model')
        ->and($model->getType()->getName())->toBe('Illuminate\Database\Eloquent\Model')
        ->and($disk->getName())->toBe('disk')
        ->and($disk->getType()->getName())->toBe('string')
        ->and($throwable->getType()->getName())->toBe('Throwable');
});
