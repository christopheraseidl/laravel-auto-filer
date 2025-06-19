<?php

namespace christopheraseidl\ModelFiler\Jobs;

use Carbon\Carbon;
use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Jobs\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsContract;
use christopheraseidl\ModelFiler\Payloads\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsPayload;
use christopheraseidl\ModelFiler\Support\FileOperationType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Cleans orphaned temporary upload files older than specified threshold.
 *
 * CAUTION: Deletes files from specified disk and path older than 24 hours by default.
 * Only use if temporary uploads are stored exclusively in the target location.
 */
final class CleanOrphanedUploads extends Job implements CleanOrphanedUploadsContract
{
    public function __construct(
        private readonly CleanOrphanedUploadsPayload $payload
    ) {
        $this->config();
    }

    /**
     * Execute cleanup operation for orphaned upload files.
     */
    public function handle(): void
    {
        if (! $this->getPayload()->isCleanupEnabled()) {
            return; // Cleanup disabled via configuration
        }

        $this->handleJob(function () {
            $dryRun = $this->getPayload()->isDryRun();
            $disk = $this->getPayload()->getDisk();
            $path = $this->getPayload()->getPath();
            $thresholdHours = $this->getPayload()->getCleanupThresholdHours();

            $files = Storage::disk($disk)->files($path);

            if ($dryRun) {
                Log::info('Initiating dry run of CleanOrphanedUploads job', [
                    'disk' => $disk,
                    'path' => $path,
                    'threshold_hours' => $thresholdHours,
                    'total_files' => count($files),
                ]);
            }

            $processedCount = $this->processFiles($files, $dryRun, $thresholdHours);

            if ($dryRun) {
                Log::info('Concluding dry run of CleanOrphanedUploads job', [
                    'files_that_would_be_deleted' => $processedCount,
                ]);
            }
        });
    }

    public function getLastModified(string $file): \DateTimeInterface
    {
        return Carbon::createFromTimestamp(
            Storage::disk($this->getPayload()->getDisk())->lastModified($file)
        );
    }

    /**
     * Get operation type identifier for job tracking.
     */
    public function getOperationType(): string
    {
        return FileOperationType::get(OperationType::Clean, OperationScope::Directory);
    }

    /**
     * Get unique identifier ensuring singleton job execution.
     */
    public function uniqueId(): string
    {
        return $this->getOperationType(); // Only one cleanup job should run at a time
    }

    public function getPayload(): CleanOrphanedUploadsPayload
    {
        return $this->payload;
    }

    private function processFiles(array $files, bool $dryRun, int $thresholdHours): int
    {
        $processedCount = 0;
        $cutoffTime = now()->subHours($thresholdHours);

        foreach ($files as $file) {
            // Check if file is older than threshold
            if ($cutoffTime->isAfter($this->getLastModified($file))) {
                $processedCount++;

                if ($dryRun) {
                    Log::info("Would delete file: {$file}");
                } else {
                    Storage::disk($this->getPayload()->getDisk())->delete($file);
                }
            }
        }

        return $processedCount;
    }
}
