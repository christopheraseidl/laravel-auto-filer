<?php

namespace christopheraseidl\HasUploads\Tests\Enums;

use christopheraseidl\HasUploads\Enums\OperationScope;

/**
 * Tests OperationScope enum structure.
 *
 * @covers \christopheraseidl\HasUploads\Enums\OperationScope
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
    $scopeCase = constant("christopheraseidl\\HasUploads\\Enums\\OperationScope::$case");

    expect($scopeCase->name)->toBe($case)
        ->and($scopeCase->value)->toBe(mb_strtolower($case));
})->with([
    ['Batch'],
    ['File'],
    ['Directory'],
]);

it('throws an exception for an invalid value', function () {
    OperationScope::from('nonexistent');
})->throws(\Error::class, '"nonexistent" is not a valid backing value for enum christopheraseidl\HasUploads\Enums\OperationScope');
