<?php

namespace christopheraseidl\ModelFiler\Jobs\Contracts;

use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploads as DeleteUploadsContract;

/**
 * Deletes a specific uploaded file.
 */
interface DeleteUploads extends Job
{
    public function __construct(DeleteUploadsContract $payload);
}
