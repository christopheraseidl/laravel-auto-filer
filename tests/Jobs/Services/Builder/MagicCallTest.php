<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\Builder;

use christopheraseidl\Reflect\Reflect;

/**
 * Tests Builder __call magic method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\Builder
 */
it('handles different value types correctly', function ($propertyName, $value, $mockeryType) {
    $this->builder->{$propertyName}($value);

    $properties = Reflect::on($this->builder)->properties;

    expect($properties)->toBe([$propertyName => $value]);
})->with([
    'null value' => ['nullProperty', null, 'null'],
    'array value' => ['arrayProperty', ['item1', 'item2', 'item3'], 'array'],
    'object value' => ['objectProperty', (function () {
        $obj = new \stdClass;
        $obj->prop = 'value';

        return $obj;
    })(), 'object'],
    'string value' => ['stringProperty', 'test string', 'string'],
    'integer value' => ['integerValue', 42, 'int'],
    'float value' => ['floatValue', 3.14, 'float'],
    'boolean true' => ['booleanTrue', true, 'bool'],
    'boolean false' => ['booleanFalse', false, 'bool'],
]);

it('allows method chaining through magic method calls', function () {
    $result = $this->builder
        ->property1('value1')
        ->property2('value2')
        ->property3('value3');

    $properties = Reflect::on($this->builder)->properties;

    expect($result)->toBe($this->builder)
        ->and($properties)->toBe([
            'property1' => 'value1',
            'property2' => 'value2',
            'property3' => 'value3',
        ]);
});

it('overwrites property when called multiple times with same method name', function () {
    $this->builder->sameProperty('first_value');
    $this->builder->sameProperty('second_value');

    $properties = Reflect::on($this->builder)->properties;

    expect($properties)->toBe(['sameProperty' => 'second_value']);
});

it('stores only the first argument when multiple arguments are passed', function () {
    $this->builder->multipleArgs('first', 'second', 'third');

    $properties = Reflect::on($this->builder)->properties;

    expect($properties)->toBe(['multipleArgs' => 'first']);
});
