<?php

namespace christopheraseidl\ModelFiler\Tests\Payloads;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Payloads\MoveUploads;

/**
 * Tests MoveUploads structure and behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Payloads\MoveUploads
 */
beforeEach(function () {
    $this->payload = new MoveUploads(
        $this->model::class,       // model class
        $this->model->id,          // model id
        'string',                  // attribute
        'images',                  // attribute type
        OperationType::Clean,      // operation type
        OperationScope::Directory, // operation scope
        'test_disk',               // disk
    );
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
