<?php

namespace christopheraseidl\ModelFiler\Tests\Payloads;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Payloads\DeleteUploads;

/**
 * Tests DeleteUploads structure and behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Payloads\DeleteUploads
 */
test('the shouldBroadcastIndividualEvents method returns false', function () {
    $payload = new DeleteUploads(
        $this->model::class,   // model class
        $this->model->id,      // model id
        'string',              // attribute
        'images',              // attribute type
        OperationType::Move,   // operation type
        OperationScope::Batch, // operation scope
        'test_disk',           // disk
    );

    expect($payload->shouldBroadcastIndividualEvents())->toBeFalse();
});
