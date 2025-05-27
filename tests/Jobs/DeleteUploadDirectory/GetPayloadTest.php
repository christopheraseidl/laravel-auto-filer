<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\DeleteUploadDirectory;

use christopheraseidl\HasUploads\Payloads\DeleteUploadDirectory as DeleteUploadDirectoryPayload;

/**
 * Tests the DeleteUploadDirectory getPayload method.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\DeleteUploadDirectory
 */
it('gets the expected payload', function () {
    expect($this->job->getPayload())
        ->toBeInstanceOf(DeleteUploadDirectoryPayload::class)
        ->toBe($this->payload);
});
