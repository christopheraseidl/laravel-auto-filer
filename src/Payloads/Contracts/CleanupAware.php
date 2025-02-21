<?php

namespace christopheraseidl\HasUploads\Payloads\Contracts;

interface CleanupAware
{
    public function getCleanupThresholdHours(): int;
}
