<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\CleanOrphanedUploads;

use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Tests the CleanOrphanedUploads handle method.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\CleanOrphanedUploads
 */
beforeEach(function () {
    config()->set('has-uploads.path', 'uploads');

    Event::fake([
        FileOperationCompleted::class,
        FileOperationFailed::class,
    ]);

    $this->oldFile = 'uploads/old_file.txt';
    $this->newFile = 'uploads/new_file.txt';
    Storage::disk($this->disk)->put($this->oldFile, 'content');
    Storage::disk($this->disk)->put($this->newFile, 'content');

    $storagePath = Storage::disk($this->disk)->path('');

    touch($storagePath.$this->oldFile, now()->subHours(25)->timestamp);
    touch($storagePath.$this->newFile, now()->subHours(12)->timestamp);
});

it('deletes files older than the threshold and broadcasts completion event', function () {
    config()->set('has-uploads.cleanup.enabled', true);
    config()->set('has-uploads.cleanup.dry_run', false);

    $this->cleaner->handle();

    expect(Storage::disk($this->disk)->exists($this->oldFile))->toBeFalse();
    expect(Storage::disk($this->disk)->exists($this->newFile))->toBeTrue();

    Event::assertDispatched(FileOperationCompleted::class);
});

it('broadcasts failure event when exception is thrown', function () {
    config()->set('has-uploads.cleanup.enabled', true);
    config()->set('has-uploads.cleanup.dry_run', false);

    Storage::shouldReceive($this->disk)->andThrow(new \Exception('Disk error'));

    $this->cleaner->handle();

    Event::assertDispatched(FileOperationFailed::class);
});

it('does not run at all when disabled', function () {
    $this->cleaner->handle();

    expect(Storage::disk($this->disk)->exists($this->oldFile))->toBeTrue();
    expect(Storage::disk($this->disk)->exists($this->newFile))->toBeTrue();

    Event::assertNothingDispatched();
});

it('logs the expected messages when dry run enabled', function () {
    config()->set('has-uploads.cleanup.enabled', true);
    config()->set('has-uploads.cleanup.dry_run', true);

    $disk = $this->cleaner->getPayload()->getDisk();
    $path = $this->cleaner->getPayload()->getPath();
    $thresholdHours = $this->cleaner->getPayload()->getCleanupThresholdHours();

    Log::spy();

    $this->cleaner->handle();

    Log::shouldHaveReceived('info')
        ->with('Initiating dry run of CleanOrphanedUploads job', [
            'disk' => $disk,
            'path' => $path,
            'threshold_hours' => $thresholdHours,
            'total_files' => 2,
        ]);

    Log::shouldHaveReceived('info')
        ->with("Would delete file: {$this->oldFile}");

    Log::shouldHaveReceived('info')
        ->with('Concluding dry run of CleanOrphanedUploads job', [
            'files_that_would_be_deleted' => 1,
        ]);
});
