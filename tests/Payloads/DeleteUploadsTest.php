<?php

namespace christopheraseidl\HasUploads\Tests\Payloads;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploads as DeleteUploadsContract;
use christopheraseidl\HasUploads\Payloads\DeleteUploads;

beforeEach(function () {
    $this->payload = new DeleteUploads(
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
    expect($this->payload)->toBeInstanceOf(DeleteUploadsContract::class);
});

test('the shouldBroadcastIndividualEvents() method returns true', function () {
    expect($this->payload->shouldBroadcastIndividualEvents())->toBeFalse();
});
