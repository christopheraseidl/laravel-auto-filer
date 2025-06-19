<?php

namespace christopheraseidl\ModelFiler\Tests\Enums;

use christopheraseidl\ModelFiler\Enums\OperationScope;

/**
 * Tests OperationScope enum structure.
 *
 * @covers \christopheraseidl\ModelFiler\Enums\OperationScope
 */
beforeEach(function () {
    $this->enum = new \ReflectionEnum(OperationScope::class);
});

it('is an enum', function () {
    expect($this->enum->isEnum())->toBeTrue();
});

it('is backed by a string', function () {
    $backer = $this->enum->getBackingType();

    expect($backer->getName())->toBe('string');
});

it('has exactly three cases', function () {
    expect(count($this->enum->getCases()))->toBe(3);
});

it('has the correct cases with the correct values', function (string $case) {
    $scopeCase = constant("christopheraseidl\\ModelFiler\\Enums\\OperationScope::$case");

    expect($scopeCase->name)->toBe($case);
    expect($scopeCase->value)->toBe(mb_strtolower($case));
})->with([
    ['Batch'],
    ['File'],
    ['Directory'],
]);

it('throws an exception for an invalid value', function () {
    $message = '"nonexistent" is not a valid backing value for enum christopheraseidl\ModelFiler\Enums\OperationScope';

    expect(fn () => OperationScope::from('nonexistent'))
        ->toThrow(\Error::class, $message);
});
