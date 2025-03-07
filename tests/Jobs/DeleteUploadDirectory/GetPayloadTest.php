<?php

use christopheraseidl\HasUploads\Payloads\DeleteUploadDirectory as DeleteUploadDirectoryPayload;

it('gets the expected payload', function () {
    expect($this->job->getPayload())
        ->toBeInstanceOf(DeleteUploadDirectoryPayload::class)
        ->toBe($this->payload);
});
