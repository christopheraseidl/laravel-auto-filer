<?php

namespace christopheraseidl\ModelFiler\Jobs\Contracts;

use christopheraseidl\ModelFiler\Contracts\DeletesFiles;
use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryPayload;

/**
 * Deletes an entire directly of uploaded files.
 */
interface DeleteUploadDirectory extends DeletesFiles, Job
{
    public function __construct(DeleteUploadDirectoryPayload $payload);
}
