<?php

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Jobs\MoveUploads;
use christopheraseidl\ModelFiler\Payloads\MoveUploads as MoveUploadsPayload;
use christopheraseidl\ModelFiler\Tests\TestModels\TestModel;

/**
 * Tests the MoveUploads getPayload method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\MoveUploads
 */
beforeEach(function () {
    $model = new TestModel;
    $this->attribute = 'string';
    $this->attributeType = 'images';
    $this->filePaths = ['file.txt'];
    $this->newDir = 'test_models/1';
    $this->payload = new MoveUploadsPayload(
        $model::class,
        1,
        $this->attribute,
        $this->attributeType,
        OperationType::Move,
        OperationScope::File,
        $this->disk,
        $this->filePaths,
        $this->newDir
    );

    $this->job = new MoveUploads($this->payload);
});

it('gets the expected payload', function () {
    $payload = $this->job->getPayload();
    expect($payload)
        ->toBeInstanceOf(MoveUploadsPayload::class)
        ->toBe($this->payload);
});
