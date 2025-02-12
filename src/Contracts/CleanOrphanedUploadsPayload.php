<?php

namespace christopheraseidl\HasUploads\Contracts;

interface CleanOrphanedUploadsPayload extends CleanupAware, Payload, SinglePath
{
    public function __construct(
        string $disk,
        string $path,
        int $cleanupThresholdHours = 24
    );
}
