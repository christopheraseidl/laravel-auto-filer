<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Contracts;

use christopheraseidl\HasUploads\Jobs\Contracts\Job;

/**
 * Tests Job interface structure.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Contracts\Job
 */
beforeEach(function () {
    $this->interface = new \ReflectionClass(Job::class);
});

it('is an interface', function () {
    expect($this->interface->isInterface())->toBeTrue();
});

it('has nine methods', function () {
    // Filter methods for only those declared by DeleteUploads.
    $methods = array_filter(
        $this->interface->getMethods(),
        function ($method) {
            return $method->getDeclaringClass()->getName() === $this->interface->getName();
        }
    );

    expect($methods)->toHaveCount(9);
});

it('extends the ShouldBeUnique and ShouldQueue interfaces', function () {
    $interfaces = $this->interface->getInterfaceNames();

    expect($interfaces)->toContain(
        'Illuminate\Contracts\Queue\ShouldBeUnique',
        'Illuminate\Contracts\Queue\ShouldQueue',
    );
});

test('the make() method is static, takes a Payload parameter, and returns static', function () {
    $name = 'make';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $payload = $parameters[0];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($method->isStatic())->toBeTrue()
        ->and($parameters)->toHaveCount(1)
        ->and($payload->getName())->toBe('payload')
        ->and($payload->getType()->getName())->toBe('christopheraseidl\HasUploads\Payloads\Contracts\Payload')
        ->and($method->getReturnType()->getName())->toBe('static');
});

test('the handle() method takes no parameters and returns void', function () {
    $name = 'handle';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(0)
        ->and($method->getReturnType()->getName())->toBe('void');
});

test('the handleJob() method takes a closure parameter and returns void', function () {
    $name = 'handleJob';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $job = $parameters[0];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(1)
        ->and($job->getName())->toBe('job')
        ->and($job->getType()->getName())->toBe('Closure')
        ->and($method->getReturnType()->getName())->toBe('void');
});

test('the getOperationType() method takes no parameters and returns a string', function () {
    $name = 'getOperationType';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(0)
        ->and($method->getReturnType()->getName())->toBe('string');
});

test('the getPayload() method takes no parameters and returns a Payload', function () {
    $name = 'getPayload';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(0)
        ->and($method->getReturnType()->getName())->toBe('christopheraseidl\HasUploads\Payloads\Contracts\Payload');
});

test('the uniqueId() method takes no parameters and returns a string', function () {
    $name = 'uniqueId';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(0)
        ->and($method->getReturnType()->getName())->toBe('string');
});

test('the retryUntil() method takes no parameters and returns a string', function () {
    $name = 'retryUntil';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(0)
        ->and($method->getReturnType()->getName())->toBe('DateTime');
});

test('the failed() method takes a Throwable parameter and returns void', function () {
    $name = 'failed';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $exception = $parameters[0];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(1)
        ->and($exception->getName())->toBe('exception')
        ->and($exception->getType()->getName())->toBe('Throwable')
        ->and($method->getReturnType()->getName())->toBe('void');
});

test('the middleware() method takes no parameters and returns an array', function () {
    $name = 'middleware';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($parameters)->toHaveCount(0)
        ->and($method->getReturnType()->getName())->toBe('array');
});
