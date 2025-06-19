<?php

namespace christopheraseidl\ModelFiler\Jobs\Contracts;

use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryPayload;

/**
 * Deletes an entire directly of uploaded files.
 */
interface DeleteUploadDirectory extends Job
{
    public function __construct(DeleteUploadDirectoryPayload $payload);
}
