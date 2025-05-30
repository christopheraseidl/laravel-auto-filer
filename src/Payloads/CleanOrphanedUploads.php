<?php

namespace christopheraseidl\HasUploads\Payloads;

use christopheraseidl\HasUploads\Payloads\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsContract;
use christopheraseidl\HasUploads\Traits\HasDisk;
use christopheraseidl\HasUploads\Traits\HasPath;

final class CleanOrphanedUploads extends Payload implements CleanOrphanedUploadsContract
{
    use HasDisk, HasPath;

    protected ?bool $isCleanupEnabled = null;

    protected ?bool $isDryRun = null;

    public function __construct(
        private readonly string $disk,
        private readonly string $path,
        private readonly ?int $cleanupThresholdHours = null
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
        return max(
            0,
            $this->cleanupThresholdHours ?? config('has-uploads.cleanup.threshold_hours')
        );
    }

    public function isCleanupEnabled(): bool
    {
        return $this->isCleanupEnabled ??= config('has-uploads.cleanup.enabled', false);
    }

    public function isDryRun(): bool
    {
        return $this->isDryRun ??= config('has-uploads.cleanup.dry_run', false);
    }
}
