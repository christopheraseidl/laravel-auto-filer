<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Contracts;

use christopheraseidl\HasUploads\Jobs\Contracts\BuilderValidator;

/**
 * Tests BuilderValidator interface structure.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Contracts\BuilderValidator
 */
beforeEach(function () {
    $this->interface = new \ReflectionClass(BuilderValidator::class);
});

it('is an interface', function () {
    expect($this->interface->isInterface())->toBeTrue();
});

it('has three methods', function () {
    $methods = $this->interface->getMethods();

    expect($methods)->toHaveCount(3);
});

test('the getValidPayloadParameter() method takes a jobClass string parameter and returns a ReflectionParameter', function () {
    $name = 'getValidPayloadParameter';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $jobClass = $parameters[0];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(1)
        ->and($jobClass->getName())->toBe('jobClass')
        ->and($jobClass->getType()->getName())->toBe('string')
        ->and($method->getReturnType()->getName())->toBe('ReflectionParameter');
});

test('the getValidPayloadClassName() method takes the correct parameters and returns a string', function () {
    $name = 'getValidPayloadClassName';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $jobClass = $parameters[0];
    $reflectionParameter = $parameters[1];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(2)
        ->and($jobClass->getName())->toBe('jobClass')
        ->and($jobClass->getType()->getName())->toBe('string')
        ->and($reflectionParameter->getName())->toBe('parameter')
        ->and($reflectionParameter->getType()->getName())->toBe('ReflectionParameter')
        ->and($method->getReturnType()->getName())->toBe('string');
});

test('the validatePropertiesExistForPayload() method takes the correct parameters and returns void', function () {
    $name = 'validatePropertiesExistForPayload';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $properties = $parameters[0];
    $payloadClass = $parameters[1];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(2)
        ->and($properties->getName())->toBe('properties')
        ->and($properties->getType()->getName())->toBe('array')
        ->and($payloadClass->getName())->toBe('payloadClass')
        ->and($payloadClass->getType()->getName())->toBe('string')
        ->and($method->getReturnType()->getName())->toBe('void');
});
