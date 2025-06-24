<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\Builder;

use christopheraseidl\Reflect\Reflect;

/**
 * Tests Builder makePayload method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\Builder
 */
it('creates payload using validator to determine correct class', function () {
    $payloadParameter = $this->mock(\ReflectionParameter::class);

    // Set minimal properties to satisfy the constructor
    Reflect::on($this->builder)->properties = ['required' => 'test'];

    $this->validator->shouldReceive('getValidPayloadParameter')
        ->once()
        ->with($this->jobClass)
        ->andReturn($payloadParameter);

    $this->validator->shouldReceive('getValidPayloadClassName')
        ->once()
        ->with($this->jobClass, $payloadParameter)
        ->andReturn($this->payloadClass);

    $this->builder->shouldReceive('getJobClass')
        ->twice()
        ->andReturn($this->jobClass);

    $result = $this->builder->makePayload();

    expect($result)->toBeInstanceOf($this->payloadClass);
});

it('passes all accumulated properties to payload constructor', function () {
    $properties = [
        'required' => 'test value',
        'paramOne' => 'John Doe',
        'paramTwo' => ['user', 'admin'],
    ];

    $payloadParameter = $this->mock(\ReflectionParameter::class);

    $this->builder->shouldReceive('getJobClass')
        ->andReturn($this->jobClass);

    Reflect::on($this->builder)->properties = $properties;

    $this->validator->shouldReceive('getValidPayloadParameter')
        ->once()
        ->with($this->jobClass)
        ->andReturn($payloadParameter);

    $this->validator->shouldReceive('getValidPayloadClassName')
        ->once()
        ->with($this->jobClass, $payloadParameter)
        ->andReturn($this->payloadClass);

    $result = $this->builder->makePayload();

    expect($result)->toBeInstanceOf($this->payloadClass);
    expect($result->required)->toBe('test value');
    expect($result->paramOne)->toBe('John Doe');
    expect($result->paramTwo)->toBe(['user', 'admin']);
});
