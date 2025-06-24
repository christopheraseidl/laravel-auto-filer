<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\CleanOrphanedUploads;

use Illuminate\Support\Facades\Log;

/**
 * Tests the CleanOrphanedUploads executeDeletion() method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\CleanOrphanedUploads
 */
beforeEach(function () {
    config()->set('model-filer.path', 'uploads');

    $this->oldFile = 'uploads/old_file.txt';
    $this->newFile = 'uploads/new_file.txt';
});

it('deletes files older than the threshold', function () {
    $this->payload->shouldReceive('isDryRun')->andReturnFalse();
    $this->payload->shouldReceive('shouldBroadcastIndividualEvents')->andReturnTrue();

    // Mock Storage to return files
    $this->cleaner->shouldReceive('getFilesToProcess')->with($this->disk, 'uploads')->andReturn([
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

    $this->cleaner->executeCleaning();
});

it('logs the expected messages when dry run enabled', function () {
    $this->payload->shouldReceive('isCleanupEnabled')->andReturnTrue();
    $this->payload->shouldReceive('isDryRun')->andReturnTrue();

    // Mock Storage to return files
    $this->cleaner->shouldReceive('getFilesToProcess')->with($this->disk, 'uploads')->andReturn([
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

    $this->cleaner->executeCleaning();
});
