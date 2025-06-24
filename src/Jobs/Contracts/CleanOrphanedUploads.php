<?php

namespace christopheraseidl\ModelFiler\Jobs\Contracts;

use christopheraseidl\ModelFiler\Contracts\DeletesFiles;
use christopheraseidl\ModelFiler\Payloads\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsPayload;

/**
 * Cleans orphaned upload files.
 */
interface CleanOrphanedUploads extends DeletesFiles, Job
{
    public function __construct(CleanOrphanedUploadsPayload $payload);

    /**
     * Gets the last modified timestamp of a file.
     */
    public function getLastModified(string $file): \DateTimeInterface;

    /**
     * Process an array of files for deletion.
     */
    public function processFiles(array $files, bool $dryRun, int $thresholdHours): int;

    /**
     * Get an array of files for processing.
     */
    public function getFilesToProcess(string $disk, string $path): array;
}
