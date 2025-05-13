<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\BaseModelEventHandler;

use christopheraseidl\HasUploads\Handlers\BaseModelEventHandler;

/**
 * Tests BaseModelEventHandler class structure, methods, and their signatures.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\BaseModelEventHandler
 */
beforeEach(function () {
    $this->base = new \ReflectionClass(BaseModelEventHandler::class);
});

it('is an abstract class', function () {
    expect($this->base->isAbstract())->toBeTrue();
});

it('contains createJobsFromAttribute() abstract method with correct signature', function () {
    $methodName = 'createJobsFromAttribute';
    $method = $this->base->getMethod($methodName);
    $parameters = $method->getParameters();
    $firstParameter = $parameters[0];
    $secondParameter = $parameters[1];
    $thirdParameter = $parameters[2];
    $returnType = $method->getReturnType();

    expect($this->base->hasMethod($methodName))->toBeTrue()
        ->and($method->isAbstract())->toBeTrue()
        ->and($method->isProtected())->toBeTrue()
        ->and($parameters)->toHaveCount(3)
        ->and($firstParameter->getName())->toBe('model')
        ->and($firstParameter->getType()->getName())->toBe('Illuminate\Database\Eloquent\Model')
        ->and($secondParameter->getName())->toBe('attribute')
        ->and($secondParameter->getType()->getName())->toBe('string')
        ->and($thirdParameter->getName())->toBe('type')
        ->and($thirdParameter->getType()->getName())->toBe('string')
        ->and($thirdParameter->allowsNull())->toBeTrue()
        ->and($thirdParameter->isDefaultValueAvailable())->toBeTrue()
        ->and($thirdParameter->getDefaultValue())->toBeNull()
        ->and($returnType)->not->toBeNull()
        ->and($returnType->getName())->toBe('array')
        ->and($returnType->allowsNull())->toBeTrue();
});

it('contains getBatchDescription() abstract method with correct signature', function () {
    $methodName = 'getBatchDescription';
    $method = $this->base->getMethod($methodName);
    $parameters = $method->getParameters();
    $returnType = $method->getReturnType();

    expect($this->base->hasMethod($methodName))->toBeTrue()
        ->and($method->isAbstract())->toBeTrue()
        ->and($method->isProtected())->toBeTrue()
        ->and($parameters)->toHaveCount(0)
        ->and($returnType)->not->toBeNull()
        ->and($returnType->getName())->toBe('string');
});

it('contains a constructor with correct signature', function () {
    $constructor = $this->base->getConstructor();
    $parameters = $constructor->getParameters();
    $uploadService = $parameters[0];
    $builder = $parameters[1];
    $batch = $parameters[2];
    $fileTracker = $parameters[3];

    expect($constructor->isPublic())->toBeTrue()
        ->and($parameters)->toHaveCount(4)
        ->and($uploadService->getName())->toBe('uploadService')
        ->and($uploadService->getType()->getName())
        ->toBe('christopheraseidl\HasUploads\Contracts\UploadService')
        ->and($builder->getName())->toBe('builder')
        ->and($builder->getType()->getName())
        ->toBe('christopheraseidl\HasUploads\Jobs\Contracts\Builder')
        ->and($batch->getName())->toBe('batch')
        ->and($batch->getType()->getName())
        ->toBe('christopheraseidl\HasUploads\Handlers\Contracts\BatchManager')
        ->and($fileTracker->getName())->toBe('fileTracker')
        ->and($fileTracker->getType()->getName())
        ->toBe('christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker');
});

it('contains handle() method with correct signature', function () {
    $methodName = 'handle';
    $method = $this->base->getMethod($methodName);
    $parameters = $method->getParameters();
    $param = $parameters[0];
    $returnType = $method->getReturnType();

    expect($this->base->hasMethod($methodName))->toBeTrue()
        ->and($method->isAbstract())->toBeFalse()
        ->and($method->isPublic())->toBeTrue()
        ->and($parameters)->toHaveCount(1)
        ->and($param->getName())->toBe('model')
        ->and($param->getType()->getName())->toBe('Illuminate\Database\Eloquent\Model')
        ->and($returnType)->not->toBeNull()
        ->and($returnType->getName())->toBe('void');
});

it('contains getAllJobs() method with correct signature', function () {
    $methodName = 'getAllJobs';
    $method = $this->base->getMethod($methodName);
    $parameters = $method->getParameters();
    $firstParameter = $parameters[0];
    $secondParameter = $parameters[1];
    $returnType = $method->getReturnType();

    expect($this->base->hasMethod($methodName))->toBeTrue()
        ->and($method->isAbstract())->toBeFalse()
        ->and($method->isProtected())->toBeTrue()
        ->and($parameters)->toHaveCount(2)
        ->and($firstParameter->getName())->toBe('model')
        ->and($firstParameter->getType()->getName())->toBe('Illuminate\Database\Eloquent\Model')
        ->and($secondParameter->getName())->toBe('filter')
        ->and($secondParameter->getType()->getName())->toBe('Closure')
        ->and($returnType)->not->toBeNull()
        ->and($returnType->getName())->toBe('array');
});
