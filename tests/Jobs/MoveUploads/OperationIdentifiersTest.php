<?php

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Jobs\MoveUploads;
use christopheraseidl\ModelFiler\Payloads\MoveUploads as MoveUploadsPayload;

/**
 * Tests the MoveUploads getOperationType and uniqueId methods.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\MoveUploads
 */
beforeEach(function () {
    $payload = new MoveUploadsPayload(
        'TestModel',
        1,
        'string',
        'images',
        OperationType::Move,
        OperationScope::File,
        $this->disk,
        ['test_models/1/file.txt']
    );

    $this->job = new MoveUploads($payload);
});

it('returns the expected operation type value', function () {
    expect($this->job->getOperationType())
        ->toBe('move_file');
});

it('provides a consistent unique identifier', function () {
    $id1 = $this->job->uniqueId();
    $id2 = $this->job->uniqueId();

    expect($id1)->toBeString()
        ->not->toBeEmpty()
        ->toBe($id2)
        ->toStartWith($this->job->getOperationType());
});
