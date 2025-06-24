<?php

namespace christopheraseidl\ModelFiler\Jobs\Contracts;

use christopheraseidl\ModelFiler\Contracts\DeletesFiles;
use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploads as DeleteUploadsContract;

/**
 * Deletes a specific uploaded file.
 */
interface DeleteUploads extends DeletesFiles, Job
{
    public function __construct(DeleteUploadsContract $payload);

    /**
     * Execute file deletion operation.
     */
    public function executeDeletion(): void;
}
