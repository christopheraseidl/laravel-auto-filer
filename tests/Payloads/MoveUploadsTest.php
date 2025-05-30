<?php

namespace christopheraseidl\HasUploads\Tests\Payloads;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Payloads\Contracts\MoveUploads as MoveUploadsContract;
use christopheraseidl\HasUploads\Payloads\MoveUploads;

/**
 * Tests MoveUploads structure and behavior.
 *
 * @covers \christopheraseidl\HasUploads\Payloads\MoveUploads
 */
beforeEach(function () {
    $this->payload = new MoveUploads(
        $this->model::class,
        1,
        'string',
        'images',
        OperationType::Clean,
        OperationScope::Directory,
        'test_disk',
    );
});

it('implements the MoveUploads contract', function () {
    expect($this->payload)->toBeInstanceOf(MoveUploadsContract::class);
});

test('the getKey method returns the expected value', function () {
    $fileIdentifier = md5(serialize(null));
    $baseKey = OperationType::Clean->value
    .'_'.OperationScope::Directory->value
    .'_'.$this->model::class
    .'_'.$this->model->id
    .'_'.$fileIdentifier;
    $baseKey = "{$baseKey}_{$fileIdentifier}";

    expect($this->payload->getKey())->toBe($baseKey);
});

test('the shouldBroadcastIndividualEvents method returns false', function () {
    expect($this->payload->shouldBroadcastIndividualEvents())->toBeFalse();
});
