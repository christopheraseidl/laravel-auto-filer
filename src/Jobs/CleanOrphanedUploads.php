<?php

namespace christopheraseidl\HasUploads\Jobs;

use Carbon\Carbon;
use christopheraseidl\HasUploads\Contracts\CleanOrphanedUploadsPayload;
use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Support\FileOperationType;
use DateTimeInterface;
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
final class CleanOrphanedUploads extends Job
{
    public function __construct(
        private readonly CleanOrphanedUploadsPayload $payload
    ) {}

    public function handle(): void
    {
        $this->handleJob(function () {
            $files = Storage::disk($this->getPayload()->getDisk())
                ->files($this->getPayload()->getPath());

            foreach ($files as $file) {
                if (now()
                    ->subHours($this->getPayload()->getCleanupThresholdHours())
                    ->isAfter($this->getLastModified($file))
                ) {
                    Storage::disk($this->getPayload()->getDisk())->delete($file);
                }
            }
        });
    }

    protected function getLastModified(string $file): DateTimeInterface
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
