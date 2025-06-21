<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\DeleteUploads;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Jobs\DeleteUploads;
use christopheraseidl\ModelFiler\Payloads\DeleteUploads as DeleteUploadsPayload;

/**
 * Tests the DeleteUploads getPayload method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\DeleteUploads
 */
it('gets the expected payload', function () {
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

    $job = new DeleteUploads($payload);

    expect($job->getPayload())
        ->toBeInstanceOf(DeleteUploadsPayload::class)
        ->toBe($payload);
});
