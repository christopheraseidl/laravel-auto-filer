<?php

namespace christopheraseidl\ModelFiler\Tests\Enums;

use christopheraseidl\ModelFiler\Enums\OperationType;

/**
 * Tests OperationType enum structure.
 *
 * @covers \christopheraseidl\ModelFiler\Enums\OperationType
 */
beforeEach(function () {
    $this->enum = new \ReflectionEnum(OperationType::class);
});

it('is an enum', function () {
    expect($this->enum->isEnum())->toBeTrue();
});

it('is backed by a string', function () {
    $backer = $this->enum->getBackingType();

    expect($backer->getName())->toBe('string');
});

it('has exactly four cases', function () {
    expect(count($this->enum->getCases()))->toBe(4);
});

it('has the correct cases with the correct values', function (string $case) {
    $scopeCase = constant("christopheraseidl\\ModelFiler\\Enums\\OperationType::$case");

    expect($scopeCase->name)->toBe($case);
    expect($scopeCase->value)->toBe(mb_strtolower($case));
})->with([
    ['Clean'],
    ['Delete'],
    ['Move'],
    ['Update'],
]);

it('throws an exception for an invalid value', function () {
    $message = '"nonexistent" is not a valid backing value for enum christopheraseidl\ModelFiler\Enums\OperationType';

    expect(fn () => OperationType::from('nonexistent'))
        ->toThrow(\Error::class, $message);
});
