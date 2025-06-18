<?php

namespace christopheraseidl\HasUploads\Jobs\Contracts;

use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploads as DeleteUploadsContract;

/**
 * Deletes a specific uploaded file.
 */
interface DeleteUploads extends Job
{
    public function __construct(DeleteUploadsContract $payload);
}
