<?php

namespace christopheraseidl\HasUploads\Tests\Enums;

use christopheraseidl\HasUploads\Enums\OperationType;

/**
 * Tests OperationType enum structure.
 *
 * @covers \christopheraseidl\HasUploads\Enums\OperationType
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
    $scopeCase = constant("christopheraseidl\\HasUploads\\Enums\\OperationType::$case");

    expect($scopeCase->name)->toBe($case)
        ->and($scopeCase->value)->toBe(mb_strtolower($case));
})->with([
    ['Clean'],
    ['Delete'],
    ['Move'],
    ['Update'],
]);

it('throws an exception for an invalid value', function () {
    OperationType::from('nonexistent');
})->throws(\Error::class, '"nonexistent" is not a valid backing value for enum christopheraseidl\HasUploads\Enums\OperationType');
