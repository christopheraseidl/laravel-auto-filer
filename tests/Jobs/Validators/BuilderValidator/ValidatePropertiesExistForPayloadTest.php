<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Validators\BuilderValidator;

use christopheraseidl\HasUploads\Tests\TestClasses\Payload\TestPayload;

/**
 * Tests BuilderValidator validatePropertiesExistForPayload method.
 *
 * @covers \christopheraseidl\HasUploads\Tests\Jobs\Validators\BuilderValidator
 */
class TestPayloadForValidation extends TestPayload
{
    public function __construct(
        public string $required,
        public ?array $array = []
    ) {}
}

class TestPayloadWithoutConstructor {}

beforeEach(function () {
    $this->payloadClass = TestPayloadForValidation::class;
});

it('is happy when parameters are all valid', function () {
    $properties = [
        'required' => 'required value',
    ];

    $this->validator->validatePropertiesExistForPayload($properties, $this->payloadClass);
})->throwsNoExceptions();

it('returns early when payload class has no constructor', function () {
    $payloadClass = TestPayloadWithoutConstructor::class;
    $properties = [];

    // This should not throw any exception
    $this->validator->validatePropertiesExistForPayload($properties, $payloadClass);
})->throwsNoExceptions();

it('throws an exception if required properties are missing', function () {
    $properties = [
        'array' => 'not required value',
    ];

    $message = 'Missing required properties for christopheraseidl\HasUploads\Tests\Jobs\Validators\BuilderValidator\TestPayloadForValidation: required';

    expect(function () use ($properties) {
        return $this->validator->validatePropertiesExistForPayload($properties, $this->payloadClass);
    })
        ->toThrow(\InvalidArgumentException::class, $message);
});
