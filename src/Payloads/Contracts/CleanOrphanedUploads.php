<?php

namespace christopheraseidl\HasUploads\Payloads\Contracts;

use christopheraseidl\HasUploads\Contracts\SinglePath;

/**
 * Provides disk and path data to the CleanOrphanedUploads job based on threshold and configuration.
 */
interface CleanOrphanedUploads extends CleanupAware, Payload, SinglePath
{
    public function __construct(
        string $disk,
        string $path,
        ?int $cleanupThresholdHours = null
    );
}
