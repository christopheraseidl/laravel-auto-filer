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

    expect($reflection->getMethods())->toHaveCount(1)
        ->and($parameters)->toHaveCount(2)
        ->and($type->getName())->toBe('type')
        ->and($type->getType()->getName())->toBe('christopheraseidl\HasUploads\Enums\OperationType')
        ->and($scope->getName())->toBe('scope')
        ->and($scope->getType()->getName())->toBe('christopheraseidl\HasUploads\Enums\OperationScope');
});

it('returns the expected value', function () {
    $expected = 'move_directory';

    $testValue = $this->support::get(OperationType::Move, OperationScope::Directory);

    expect($testValue)->toBe($expected);
});
