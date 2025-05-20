<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Validators\BuilderValidator;

use christopheraseidl\HasUploads\Tests\TestClasses\TestJob;
use christopheraseidl\HasUploads\Tests\TestClasses\TestPayload;

/**
 * Tests BuilderValidator getValidPayloadClassName() method.
 *
 * @covers \christopheraseidl\HasUploads\Tests\Jobs\Validators\BuilderValidator
 */
beforeEach(function () {
    $this->payload = new TestPayload;
    $this->job = new TestJob($this->payload);
    $this->jobClass = get_class($this->job);
    $reflection = new \ReflectionClass($this->job);
    $constructor = $reflection->getConstructor();
    $this->parameter = $constructor->getParameters()[0];
});

it('returns the expected payload class name', function () {
    $testValue = $this->validator->getValidPayloadClassName($this->jobClass, $this->parameter);

    expect($testValue)->toEqual(get_class($this->payload));
});

it('throws an exception when the parameter argument is not a class', function () {
    $reflectionParameterMock = \Mockery::mock(\ReflectionParameter::class)->makePartial();
    $reflectionNamedTypeMock = \Mockery::mock(\ReflectionNamedType::class)->makePartial();

    $reflectionParameterMock->shouldReceive('getType')->andReturn($reflectionNamedTypeMock);
    $reflectionNamedTypeMock->shouldReceive('isBuiltin')->andReturn(true);

    expect(fn () => $this->validator->getValidPayloadClassName($this->jobClass, $reflectionParameterMock))
        ->toThrow(\InvalidArgumentException::class, "Parameter of christopheraseidl\HasUploads\Tests\TestClasses\TestJob constructor must be a class type.");
});

it('throws an exception when the parameter type is not a ReflectionNamedType', function () {
    $reflectionParameterMock = \Mockery::mock(\ReflectionParameter::class)->makePartial();

    $reflectionParameterMock->shouldReceive('getType')->andReturn(
        \Mockery::mock(\ReflectionType::class)->makePartial()
    );

    expect(fn () => $this->validator->getValidPayloadClassName($this->jobClass, $reflectionParameterMock))
        ->toThrow(\InvalidArgumentException::class, "Parameter of christopheraseidl\HasUploads\Tests\TestClasses\TestJob constructor must be a class type.");
});
