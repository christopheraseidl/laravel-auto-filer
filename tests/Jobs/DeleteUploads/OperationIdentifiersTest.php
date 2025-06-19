<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\DeleteUploads;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Jobs\DeleteUploads;
use christopheraseidl\ModelFiler\Payloads\DeleteUploads as DeleteUploadsPayload;

/**
 * Tests the DeleteUploads getOperationType and uniqueId methods.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\DeleteUploads
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
