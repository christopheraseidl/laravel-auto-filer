<?php

namespace christopheraseidl\HasUploads\Payloads;

use christopheraseidl\HasUploads\Payloads\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsContract;
use christopheraseidl\HasUploads\Traits\HasDisk;
use christopheraseidl\HasUploads\Traits\HasPath;

final class CleanOrphanedUploads extends Payload implements CleanOrphanedUploadsContract
{
    use HasDisk, HasPath;

    public function __construct(
        private readonly string $disk,
        private readonly string $path,
        private readonly int $cleanupThresholdHours = 24
    ) {}

    public function getKey(): string
    {
        return 'clean_orphaned_uploads';
    }

    public function shouldBroadcastIndividualEvents(): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return [
            'disk' => $this->disk,
            'path' => $this->path,
            'cleanup_threshold_hours' => $this->cleanupThresholdHours,
        ];
    }

    public function getCleanupThresholdHours(): int
    {
        return $this->cleanupThresholdHours;
    }
}
