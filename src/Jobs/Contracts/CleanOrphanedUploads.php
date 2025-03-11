<?php

namespace christopheraseidl\HasUploads\Jobs\Contracts;

use christopheraseidl\HasUploads\Payloads\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsPayload;

interface CleanOrphanedUploads extends Job
{
    public function __construct(CleanOrphanedUploadsPayload $payload);

    public function getLastModified(string $file): \DateTimeInterface;
}
