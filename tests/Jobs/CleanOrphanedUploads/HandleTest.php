<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\CleanOrphanedUploads;

use christopheraseidl\ModelFiler\Events\FileOperationCompleted;
use christopheraseidl\ModelFiler\Events\FileOperationFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Tests the CleanOrphanedUploads handle method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\CleanOrphanedUploads
 */
beforeEach(function () {
    config()->set('model-filer.path', 'uploads');

    Event::fake([
        FileOperationCompleted::class,
        FileOperationFailed::class,
    ]);

    $this->oldFile = 'uploads/old_file.txt';
    $this->newFile = 'uploads/new_file.txt';
});

it('deletes files older than the threshold and broadcasts completion event', function () {
    $this->payload->shouldReceive('isCleanupEnabled')->once()->andReturnTrue();
    $this->payload->shouldReceive('isDryRun')->andReturnFalse();
    $this->payload->shouldReceive('shouldBroadcastIndividualEvents')->andReturnTrue();

    // Mock Storage to return files
    Storage::shouldReceive('disk')->with($this->disk)->andReturnSelf();
    Storage::shouldReceive('files')->with('uploads')->andReturn([
        $this->oldFile,
        $this->newFile,
    ]);

    // Mock last modified times
    $this->cleaner->shouldReceive('getLastModified')
        ->with($this->oldFile)
        ->andReturn(now()->subHours(25));
    $this->cleaner->shouldReceive('getLastModified')
        ->with($this->newFile)
        ->andReturn(now()->subHours(12));

    // Expect only old file to be deleted
    $this->deleter->shouldReceive('attemptDelete')
        ->once()
        ->with($this->disk, $this->oldFile);

    $this->cleaner->handle();

    Event::assertDispatched(FileOperationCompleted::class);
});

it('broadcasts failure event when exception is thrown', function () {
    $this->payload->shouldReceive('isCleanupEnabled')->andReturnTrue();
    $this->payload->shouldReceive('isDryRun')->andReturnFalse();
    $this->payload->shouldReceive('shouldBroadcastIndividualEvents')->andReturnTrue();

    $this->cleaner->shouldReceive('processFiles')
        ->once()
        ->andThrow(new \Exception('Disk error'));

    $this->cleaner->handle();

    Event::assertDispatched(FileOperationFailed::class);
});

it('does not run at all when disabled', function () {
    $this->payload->shouldReceive('isCleanupEnabled')->once()->andReturn(false);
    $this->payload->shouldReceive('isDryRun')->andReturnFalse();

    $this->cleaner->shouldReceive('processFiles')->never();

    $this->cleaner->handle();

    Event::assertNothingDispatched();
});

it('logs the expected messages when dry run enabled', function () {
    $this->payload->shouldReceive('isCleanupEnabled')->andReturnTrue();
    $this->payload->shouldReceive('isDryRun')->andReturnTrue();
    $this->payload->shouldReceive('shouldBroadcastIndividualEvents')->andReturnFalse();

    // Mock Storage to return files
    Storage::shouldReceive('disk')->with($this->disk)->andReturnSelf();
    Storage::shouldReceive('files')->with('uploads')->andReturn([
        $this->oldFile,
        $this->newFile,
    ]);

    // Mock last modified times
    $this->cleaner->shouldReceive('getLastModified')
        ->with($this->oldFile)
        ->andReturn(now()->subHours(25));
    $this->cleaner->shouldReceive('getLastModified')
        ->with($this->newFile)
        ->andReturn(now()->subHours(12));

    $disk = $this->cleaner->getPayload()->getDisk();
    $path = $this->cleaner->getPayload()->getPath();
    $thresholdHours = $this->cleaner->getPayload()->getCleanupThresholdHours();

    Log::shouldReceive('info')
        ->once()
        ->with('Initiating dry run of CleanOrphanedUploads job', [
            'disk' => $disk,
            'path' => $path,
            'threshold_hours' => $thresholdHours,
            'total_files' => 2,
        ]);
    Log::shouldReceive('info')
        ->once()
        ->with("Would delete file: {$this->oldFile}");
    Log::shouldReceive('info')
        ->once()
        ->with('Concluding dry run of CleanOrphanedUploads job', [
            'files_that_would_be_deleted' => 1,
        ]);

    $this->cleaner->handle();
});
