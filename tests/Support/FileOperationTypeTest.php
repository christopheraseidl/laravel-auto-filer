<?php

namespace christopheraseidl\HasUploads\Tests\Support;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Support\FileOperationType;

/**
 * Tests FileOperationType structure and behavior.
 *
 * @covers \christopheraseidl\HasUploads\Support\FileOperationType
 */
beforeEach(function () {
    $this->support = new FileOperationType;
});

it('only has one static get() method with the correct parameters and return type', function () {
    $reflection = new \ReflectionClass($this->support);
    $method = $reflection->getMethod('get');
    $parameters = $method->getParameters();
    $type = $parameters[0];
    $scope = $parameters[1];

    expect($reflection->getMethods())->toHaveCount(1);
    expect($parameters)->toHaveCount(2);
    expect($type->getName())->toBe('type');
    expect($type->getType()->getName())->toBe(OperationType::class);
    expect($scope->getName())->toBe('scope');
    expect($scope->getType()->getName())->toBe(OperationScope::class);
});

it('returns the expected value', function () {
    $expected = 'move_directory';

    $testValue = $this->support::get(OperationType::Move, OperationScope::Directory);

    expect($testValue)->toBe($expected);
});
