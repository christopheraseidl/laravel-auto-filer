<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\MoveUploads;

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
it('gets the expected payload', function () {
    $model = new TestModel;

    $attribute = 'string';
    $attributeType = 'images';
    $filePaths = ['file.txt'];
    $newDir = 'test_models/1';

    $this->payload = new MoveUploadsPayload(
        $model::class,
        1,
        $attribute,
        $attributeType,
        OperationType::Move,
        OperationScope::File,
        $this->disk,
        $filePaths,
        $newDir
    );

    $this->job = new MoveUploads($this->payload);

    $payload = $this->job->getPayload();
    expect($payload)
        ->toBeInstanceOf(MoveUploadsPayload::class)
        ->toBe($this->payload);
});
