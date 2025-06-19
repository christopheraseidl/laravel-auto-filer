<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Validators\BuilderValidator;

use christopheraseidl\HasUploads\Tests\TestClasses\Payload\TestPayloadNoConstructor;
use christopheraseidl\HasUploads\Tests\TestClasses\TestJob;
use Mockery\MockInterface;

/**
 * Tests BuilderValidator getValidPayloadClassName method.
 *
 * @covers \christopheraseidl\HasUploads\Tests\Jobs\Validators\BuilderValidator
 */
beforeEach(function () {
    $this->payload = new TestPayloadNoConstructor;
    $this->job = new TestJob($this->payload);
    $this->jobClass = $this->job::class;
    $reflection = new \ReflectionClass($this->job);
    $constructor = $reflection->getConstructor();
    $this->parameter = $constructor->getParameters()[0];
});

it('returns the expected payload class name', function () {
    $testValue = $this->validator->getValidPayloadClassName($this->jobClass, $this->parameter);

    expect($testValue)->toEqual('christopheraseidl\HasUploads\Payloads\Payload');
});

it('throws an exception when the parameter argument is not a class', function () {
    $reflectionParameterMock = $this->partialMock(\ReflectionParameter::class);
    $reflectionNamedTypeMock = $this->partialMock(\ReflectionNamedType::class);

    $reflectionParameterMock->shouldReceive('getType')->andReturn($reflectionNamedTypeMock);
    $reflectionNamedTypeMock->shouldReceive('isBuiltin')->andReturn(true);

    expect(fn () => $this->validator->getValidPayloadClassName($this->jobClass, $reflectionParameterMock))
        ->toThrow(\InvalidArgumentException::class, "Parameter of christopheraseidl\HasUploads\Tests\TestClasses\TestJob constructor must be a class type.");
});

it('throws an exception when the parameter type is not a ReflectionNamedType', function () {
    $reflectionParameterMock = $this->partialMock(\ReflectionParameter::class, function (MockInterface $mock) {
        $mock->shouldReceive('getType')->andReturn(
            \Mockery::mock(\ReflectionType::class)->makePartial()
        );
    });

    $this->validator->getValidPayloadClassName($this->jobClass, $reflectionParameterMock);
})->throws(\InvalidArgumentException::class, "Parameter of christopheraseidl\HasUploads\Tests\TestClasses\TestJob constructor must be a class type.");
