<?php

namespace christopheraseidl\HasUploads\Jobs\Contracts;

use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryPayload;

interface DeleteUploadDirectory extends Job
{
    public function __construct(DeleteUploadDirectoryPayload $payload);
}
