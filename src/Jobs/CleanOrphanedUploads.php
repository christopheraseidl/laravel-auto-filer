<?php

namespace christopheraseidl\HasUploads\Jobs;

use Carbon\Carbon;
use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsContract;
use christopheraseidl\HasUploads\Payloads\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsPayload;
use christopheraseidl\HasUploads\Support\FileOperationType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * CAUTION: This job deletes files from the specified disk and path that are
 * older than 24 hours (by default). It assumes any files in this location
 * are temporary uploads that failed to be moved to their model directories. If
 * your application stores other files in this location, DO NOT use this job.
 *
 * @see \christopheraseidl\HasUploads\Support\UploadService For the upload path
 * configuration.
 */
final class CleanOrphanedUploads extends Job implements CleanOrphanedUploadsContract
{
    public function __construct(
        private readonly CleanOrphanedUploadsPayload $payload
    ) {
        $this->config();
    }

    public function handle(): void
    {
        if (! $this->getPayload()->isCleanupEnabled()) {
            return;
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

            $processedCount = 0;

            foreach ($files as $file) {
                if (now()
                    ->subHours($thresholdHours)
                    ->isAfter($this->getLastModified($file))
                ) {
                    $processedCount++;

                    if ($dryRun) {
                        Log::info("Would delete file: {$file}");
                    } else {
                        Storage::disk($disk)->delete($file);
                    }
                }
            }

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

    public function getOperationType(): string
    {
        return FileOperationType::get(OperationType::Clean, OperationScope::Directory);
    }

    public function uniqueId(): string
    {
        return $this->getOperationType();
    }

    public function getPayload(): CleanOrphanedUploadsPayload
    {
        return $this->payload;
    }
}
