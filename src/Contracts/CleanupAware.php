<?php

namespace christopheraseidl\HasUploads\Contracts;

interface CleanupAware
{
    public function getCleanupThresholdHours(): int;
}
