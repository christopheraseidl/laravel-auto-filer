<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\DeleteUploads;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\DeleteUploads;
use christopheraseidl\HasUploads\Payloads\DeleteUploads as DeleteUploadsPayload;

/**
 * Tests the DeleteUploads getOperationType and uniqueId methods.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\DeleteUploads
 */
beforeEach(function () {
    $payload = new DeleteUploadsPayload(
        'TestModel',
        1,
        'string',
        'images',
        OperationType::Delete,
        OperationScope::File,
        $this->disk,
        ['test_models/1/file.txt']
    );

    $this->job = new DeleteUploads($payload);
});

it('returns the expected operation type value', function () {
    expect($this->job->getOperationType())
        ->toBe('delete_file');
});

it('provides a consistent unique identifier', function () {
    $id1 = $this->job->uniqueId();
    $id2 = $this->job->uniqueId();

    expect($id1)->toBeString()
        ->not->toBeEmpty()
        ->toBe($id2)
        ->toStartWith($this->job->getOperationType());
});
