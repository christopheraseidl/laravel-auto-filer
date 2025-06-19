<?php

namespace christopheraseidl\ModelFiler\Tests\Payloads;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploads as DeleteUploadsContract;
use christopheraseidl\ModelFiler\Payloads\DeleteUploads;

/**
 * Tests DeleteUploads structure and behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Payloads\DeleteUploads
 */
beforeEach(function () {
    $this->payload = new DeleteUploads(
        $this->model::class,
        1,
        'string',
        'images',
        OperationType::Move,
        OperationScope::Batch,
        'test_disk',
    );
});

it('implements the BatchUpdate contract', function () {
    expect($this->payload)->toBeInstanceOf(DeleteUploadsContract::class);
});

test('the shouldBroadcastIndividualEvents method returns true', function () {
    expect($this->payload->shouldBroadcastIndividualEvents())->toBeFalse();
});
