<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\DeleteUploads;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\DeleteUploads;
use christopheraseidl\HasUploads\Payloads\DeleteUploads as DeleteUploadsPayload;

/**
 * Tests the DeleteUploads getPayload method.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\DeleteUploads
 */
beforeEach(function () {
    $this->payload = new DeleteUploadsPayload(
        'TestModel',
        1,
        'string',
        'images',
        OperationType::Delete,
        OperationScope::File,
        $this->disk,
        ['test_models/1/file.txt']
    );

    $this->job = new DeleteUploads($this->payload);
});

it('gets the expected payload', function () {
    expect($this->job->getPayload())
        ->toBeInstanceOf(DeleteUploadsPayload::class)
        ->toBe($this->payload);
});
