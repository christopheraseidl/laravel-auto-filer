<?php

namespace christopheraseidl\AutoFiler\Jobs;

use christopheraseidl\AutoFiler\Contracts\FileDeleter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes files in the temporary uploads folder older than the threshold.
 * Dry mode enables previewing deletions before performing them.
 */
class CleanOrphanedUploads implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private readonly string $path;

    private readonly bool $dryRun;

    public function __construct()
    {
        $this->path = config('auto-filer.temp_directory', null);
        $this->dryRun = config('auto-filer.cleanup.dry_run', true);

        $this->onConnection(config('auto-filer.queue_connection'));
        $this->onQueue(config('auto-filer.queue'));
    }

    /**
     * Delete files in the temporary uploads folder older than the threshold.
     */
    public function handle(FileDeleter $deleter): void
    {
        if (! config('auto-filer.cleanup.enabled')) {
            return;
        }

        $disk = config('auto-filer.disk');
        $threshold = now()->subHours(config('auto-filer.cleanup.threshold_hours'));
        $files = Storage::disk($disk)->files($this->path);
        $deleted = 0;

        foreach ($files as $file) {
            $lastModified = Storage::disk($disk)->lastModified($file);

            if ($threshold->timestamp > $lastModified) {
                if ($this->dryRun) {
                    Log::info("Would delete orphaned file: {$file}");
                } else {
                    $deleter->delete($file);
                }
                $deleted++;
            }
        }

        Log::info('Orphaned file cleanup completed', [
            'path' => $this->path,
            'deleted' => $deleted,
            'dry_run' => $this->dryRun,
        ]);
    }

    public function uniqueId(): string
    {
        return 'cleanup_'.hash('sha256', $this->path);
    }
}
