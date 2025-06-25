<?php

namespace christopheraseidl\ModelFiler\Payloads;

use christopheraseidl\ModelFiler\Payloads\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsContract;
use christopheraseidl\ModelFiler\Traits\HasPath;

/**
 * Provides relevant data to the CleanOrphanedUploads job based on threshold and configuration.
 */
class CleanOrphanedUploads extends Payload implements CleanOrphanedUploadsContract
{
    use HasPath;

    protected ?bool $isCleanupEnabled = null;

    protected ?bool $isDryRun = null;

    public function __construct(
        private readonly string $disk,
        private readonly string $path,
        private readonly ?int $cleanupThresholdHours = null
    ) {}

    /**
     * Return cleanup threshold hours with configuration fallback.
     */
    public function getCleanupThresholdHours(): int
    {
        return max(
            0,
            $this->cleanupThresholdHours ?? config('model-filer.cleanup.threshold_hours')
        );
    }

    /**
     * Check if cleanup feature is enabled.
     */
    public function isCleanupEnabled(): bool
    {
        return $this->isCleanupEnabled ??= config('model-filer.cleanup.enabled', false);
    }

    /**
     * Check if running in dry run mode.
     */
    public function isDryRun(): bool
    {
        return $this->isDryRun ??= config('model-filer.cleanup.dry_run', false);
    }

    /**
     * Return unique identifier for this payload type.
     */
    public function getKey(): string
    {
        return 'clean_orphaned_uploads';
    }

    /**
     * Return storage disk name.
     */
    public function getDisk(): string
    {
        return $this->disk;
    }

    /**
     * Determine whether individual events should be broadcast.
     */
    public function shouldBroadcastIndividualEvents(): bool
    {
        return true;
    }

    /**
     * Convert payload data to array representation.
     */
    public function toArray(): array
    {
        return [
            'disk' => $this->disk,
            'path' => $this->path,
            'cleanup_threshold_hours' => $this->getCleanupThresholdHours(),
        ];
    }
}
