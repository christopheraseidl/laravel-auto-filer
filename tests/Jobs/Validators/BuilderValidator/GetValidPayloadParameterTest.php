<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Validators\BuilderValidator;

use christopheraseidl\HasUploads\Tests\TestClasses\Payload\TestPayloadNoConstructor;
use christopheraseidl\HasUploads\Tests\TestClasses\TestJob;

/**
 * Tests BuilderValidator getValidPayloadParameter method.
 *
 * @covers \christopheraseidl\HasUploads\Tests\Jobs\Validators\BuilderValidator
 */
class TestJobWithoutConstructor {}

class TestJobWithEmptyConstructor
{
    public function __construct() {}
}

class TestJobWithMultipleParameters
{
    public function __construct(string $paramOne, string $paramTwo) {}
}

beforeEach(function () {
    $this->payload = new TestPayloadNoConstructor;
    $this->job = new TestJob($this->payload);
    $this->jobClass = get_class($this->job);
    $this->reflection = new \ReflectionClass($this->job);
    $this->constructor = $this->reflection->getConstructor();

});

it('returns the expected ReflectionParameter value', function () {
    $parameter = $this->constructor->getParameters()[0];

    $testValue = $this->validator->getValidPayloadParameter($this->jobClass);

    expect($testValue)->toEqual($parameter);
});

it('throws an exception if there is no constructor', function () {
    $jobClass = TestJobWithoutConstructor::class;

    expect(function () use ($jobClass) {
        $this->validator->getValidPayloadParameter($jobClass);
    })->toThrow(\InvalidArgumentException::class, 'TestJobWithoutConstructor must have a constructor.');
});

it('throws an exception if the constructor does not have exactly one parameter', function () {
    $jobEmptyConstructor = TestJobWithEmptyConstructor::class;
    $jobMultipleParametersConstructor = TestJobWithMultipleParameters::class;

    expect(function () use ($jobEmptyConstructor) {
        $this->validator->getValidPayloadParameter($jobEmptyConstructor);
    })->toThrow(\InvalidArgumentException::class, "{$jobEmptyConstructor} constructor must have one parameter.");

    expect(function () use ($jobMultipleParametersConstructor) {
        $this->validator->getValidPayloadParameter($jobMultipleParametersConstructor);
    })->toThrow(\InvalidArgumentException::class, "{$jobMultipleParametersConstructor} constructor must have one parameter.");
});
