<?php

namespace christopheraseidl\ModelFiler\Payloads\Contracts;

use christopheraseidl\ModelFiler\Contracts\SinglePath;

/**
 * Provides disk and path data to the CleanOrphanedUploads job based on threshold and configuration.
 */
interface CleanOrphanedUploads extends Payload, SinglePath
{
    public function __construct(
        string $disk,
        string $path,
        ?int $cleanupThresholdHours = null
    );

    /**
     * Return cleanup threshold hours with configuration fallback.
     */
    public function getCleanupThresholdHours(): int;

    /**
     * Check if cleanup feature is enabled.
     */
    public function isCleanupEnabled(): bool;

    /**
     * Check if running in dry run mode.
     */
    public function isDryRun(): bool;

    /**
     * Return unique identifier for this payload type.
     */
    public function getKey(): string;

    /**
     * Determine whether individual events should be broadcast.
     */
    public function shouldBroadcastIndividualEvents(): bool;

    /**
     * Convert payload data to array representation.
     */
    public function toArray(): array;
}
