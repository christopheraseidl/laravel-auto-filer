<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\DeleteUploadDirectory;

use christopheraseidl\ModelFiler\Jobs\DeleteUploadDirectory;
use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryPayloadContract;
use christopheraseidl\ModelFiler\Payloads\DeleteUploadDirectory as DeleteUploadDirectoryPayload;
use christopheraseidl\ModelFiler\Tests\TestModels\TestModel;

/**
 * Tests the DeleteUploadDirectory getPayload method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\DeleteUploadDirectory
 */
it('gets the expected payload', function () {
    $payload = new DeleteUploadDirectoryPayload(
        TestModel::class,
        $this->model->id,
        $this->disk,
        $this->path
    );

    $deleter = new DeleteUploadDirectory($payload);

    expect($deleter->getPayload())
        ->toBeInstanceOf(DeleteUploadDirectoryPayloadContract::class)
        ->toBe($payload);
});
