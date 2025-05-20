<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Contracts;

use christopheraseidl\HasUploads\Jobs\Contracts\Builder;

/**
 * Tests Builder interface structure.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Contracts\Builder
 */
beforeEach(function () {
    $this->interface = new \ReflectionClass(Builder::class);
});

it('is an interface', function () {
    expect($this->interface->isInterface())->toBeTrue();
});

it('has six methods', function () {
    $methods = $this->interface->getMethods();

    expect($methods)->toHaveCount(6);
});

test('the constructor takes a BuilderValidator argument', function () {
    $constructor = $this->interface->getConstructor();
    $parameters = $constructor->getParameters();
    $validator = $parameters[0];

    expect($parameters)->toHaveCount(1)
        ->and($validator->getName())->toBe('validator')
        ->and($validator->getType()->getName())->toBe('christopheraseidl\HasUploads\Jobs\Contracts\BuilderValidator');
});

test('the job() method takes a jobClass string parameter and returns self', function () {
    $name = 'job';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $jobClass = $parameters[0];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(1)
        ->and($jobClass->getName())->toBe('jobClass')
        ->and($jobClass->getType()->getName())->toBe('string')
        ->and($method->getReturnType()->getName())->toBe('self');
});

test('the __call() method returns self', function () {
    $name = '__call';
    $method = $this->interface->getMethod($name);

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($method->getReturnType()->getName())->toBe('self');
});

test('the makePayload() method takes no parameters and returns a Payload', function () {
    $name = 'makePayload';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(0)
        ->and($method->getReturnType()->getName())->toBe('christopheraseidl\HasUploads\Payloads\Contracts\Payload');
});

test('the makeJob() method takes a Payload parameter and returns a Job', function () {
    $name = 'makeJob';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $payload = $parameters[0];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(1)
        ->and($payload->getName())->toBe('payload')
        ->and($payload->getType()->getName())->toBe('christopheraseidl\HasUploads\Payloads\Contracts\Payload')
        ->and($method->getReturnType()->getName())->toBe('christopheraseidl\HasUploads\Jobs\Contracts\Job');
});

test('the build() method takes no parameters and returns a Job', function () {
    $name = 'build';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(0)
        ->and($method->getReturnType()->getName())->toBe('christopheraseidl\HasUploads\Jobs\Contracts\Job');
});
