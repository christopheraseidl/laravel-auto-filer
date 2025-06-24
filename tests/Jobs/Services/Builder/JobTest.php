<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\Builder;

use christopheraseidl\Reflect\Reflect;

/**
 * Tests Builder job method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\Builder
 */
it('sets job class and returns builder instance', function () {
    $result = $this->builder->job($this->jobClass);
    $jobClass = Reflect::on($this->builder)->jobClass;

    expect($result)->toBe($this->builder)
        ->and($jobClass)->toBe($this->jobClass);
});

it('allows method chaining after setting job class', function () {
    $result = $this->builder
        ->job($this->jobClass)
        ->property1('value1')
        ->property2('value2');

    $jobClass = Reflect::on($this->builder)->jobClass;
    $properties = Reflect::on($this->builder)->properties;

    expect($result)->toBe($this->builder)
        ->and($jobClass)->toBe($this->jobClass)
        ->and($properties)->toBe([
            'property1' => 'value1',
            'property2' => 'value2',
        ]);
});

it('overwrites job class when called multiple times', function () {
    $firstJobClass = 'FirstJob';
    $secondJobClass = 'SecondJob';

    $this->builder->job($firstJobClass);
    $this->builder->job($secondJobClass);

    $jobClass = Reflect::on($this->builder)->jobClass;

    expect($jobClass)->toBe($secondJobClass);
});

it('can set job class after setting properties', function () {
    $this->builder
        ->property1('value1')
        ->job($this->jobClass)
        ->property2('value2');

    $jobClass = Reflect::on($this->builder)->jobClass;
    $properties = Reflect::on($this->builder)->properties;

    expect($jobClass)->toBe($this->jobClass)
        ->and($properties)->toBe([
            'property1' => 'value1',
            'property2' => 'value2',
        ]);
});
