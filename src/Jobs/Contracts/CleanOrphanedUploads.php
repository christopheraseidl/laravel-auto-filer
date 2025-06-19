<?php

namespace christopheraseidl\ModelFiler\Jobs\Contracts;

use christopheraseidl\ModelFiler\Payloads\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsPayload;

/**
 * Cleans orphaned upload files.
 */
interface CleanOrphanedUploads extends Job
{
    public function __construct(CleanOrphanedUploadsPayload $payload);

    /**
     * Gets the last modified timestamp of a file.
     */
    public function getLastModified(string $file): \DateTimeInterface;
}
