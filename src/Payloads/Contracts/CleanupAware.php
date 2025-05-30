<?php

namespace christopheraseidl\HasUploads\Payloads\Contracts;

interface CleanupAware
{
    public function getCleanupThresholdHours(): int;

    public function isCleanupEnabled(): bool;

    public function isDryRun(): bool;
}
