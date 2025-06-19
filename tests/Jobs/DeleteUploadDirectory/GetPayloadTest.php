<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\DeleteUploadDirectory;

use christopheraseidl\ModelFiler\Payloads\DeleteUploadDirectory as DeleteUploadDirectoryPayload;

/**
 * Tests the DeleteUploadDirectory getPayload method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\DeleteUploadDirectory
 */
it('gets the expected payload', function () {
    expect($this->job->getPayload())
        ->toBeInstanceOf(DeleteUploadDirectoryPayload::class)
        ->toBe($this->payload);
});
