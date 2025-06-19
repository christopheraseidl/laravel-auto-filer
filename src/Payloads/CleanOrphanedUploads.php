<?php

namespace christopheraseidl\ModelFiler\Payloads;

use christopheraseidl\ModelFiler\Payloads\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsContract;
use christopheraseidl\ModelFiler\Traits\HasDisk;
use christopheraseidl\ModelFiler\Traits\HasPath;

/**
 * Provides relevant data to the CleanOrphanedUploads job based on threshold and configuration.
 */
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

    /**
     * Get cleanup threshold with fallback to configuration default.
     */
    public function getCleanupThresholdHours(): int
    {
        return max(
            0,
            $this->cleanupThresholdHours ?? config('model-filer.cleanup.threshold_hours')
        );
    }

    public function isCleanupEnabled(): bool
    {
        return $this->isCleanupEnabled ??= config('model-filer.cleanup.enabled', false);
    }

    public function isDryRun(): bool
    {
        return $this->isDryRun ??= config('model-filer.cleanup.dry_run', false);
    }
}
