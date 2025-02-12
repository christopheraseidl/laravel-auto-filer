<?php

namespace christopheraseidl\HasUploads\Payloads;

use christopheraseidl\HasUploads\Contracts\CleanOrphanedUploadsPayload as ContractsCleanOrphanedUploadsPayload;
use christopheraseidl\HasUploads\Traits\HasDisk;
use christopheraseidl\HasUploads\Traits\HasPath;

final class CleanOrphanedUploadsPayload implements ContractsCleanOrphanedUploadsPayload
{
    use HasDisk, HasPath;

    public function __construct(
        private readonly string $disk,
        private readonly string $path,
        private readonly int $cleanupThresholdHours = 24
    ) {}

    public static function make(...$args): ?static
    {
        [
            $disk,
            $path,
            $cleanupThresholdHours
        ] = $args;

        return new self(
            disk: $disk,
            path: $path,
            cleanupThresholdHours: $cleanupThresholdHours
        );
    }

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
