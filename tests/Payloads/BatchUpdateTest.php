<?php

namespace christopheraseidl\HasUploads\Tests\Payloads;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Payloads\BatchUpdate;
use christopheraseidl\HasUploads\Payloads\Contracts\BatchUpdate as BatchUpdateContract;

beforeEach(function () {
    $this->payload = new BatchUpdate(
        get_class($this->model),
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

test('shouldBroadcastIndividualEvents() returns true', function () {
    expect($this->payload->shouldBroadcastIndividualEvents())
        ->toBeTrue();
});
