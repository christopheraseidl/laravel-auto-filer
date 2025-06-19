<?php

namespace christopheraseidl\ModelFiler\Tests\Payloads;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Payloads\BatchUpdate;
use christopheraseidl\ModelFiler\Payloads\Contracts\BatchUpdate as BatchUpdateContract;

/**
 * Tests BatchUpdate structure and behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Payloads\BatchUpdate
 */
beforeEach(function () {
    $this->payload = new BatchUpdate(
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
    expect($this->payload)->toBeInstanceOf(BatchUpdateContract::class);
});

test('shouldBroadcastIndividualEvents returns true', function () {
    expect($this->payload->shouldBroadcastIndividualEvents())
        ->toBeTrue();
});
