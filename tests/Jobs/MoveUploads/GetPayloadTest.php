<?php

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\MoveUploads;
use christopheraseidl\HasUploads\Payloads\MoveUploads as MoveUploadsPayload;
use christopheraseidl\HasUploads\Tests\TestModels\TestModel;

beforeEach(function () {
    $model = new TestModel;
    $this->attribute = 'string';
    $this->attributeType = 'images';
    $this->filePaths = ['file.txt'];
    $this->newDir = 'test_models/1';
    $this->payload = new MoveUploadsPayload(
        get_class($model),
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
