<?php

namespace christopheraseidl\HasUploads\Payloads\Contracts;

use christopheraseidl\HasUploads\Contracts\SinglePath;

interface CleanOrphanedUploads extends CleanupAware, Payload, SinglePath
{
    public function __construct(
        string $disk,
        string $path,
        int $cleanupThresholdHours = 24
    );
}
