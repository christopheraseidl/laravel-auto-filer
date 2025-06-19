<?php

namespace christopheraseidl\ModelFiler\Payloads\Contracts;

use christopheraseidl\ModelFiler\Contracts\SinglePath;

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
