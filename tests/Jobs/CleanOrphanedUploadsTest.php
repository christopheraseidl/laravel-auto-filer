<?php

namespace christopheraseidl\AutoFiler\Tests\Jobs;

use christopheraseidl\AutoFiler\Contracts\FileDeleter;
use christopheraseidl\AutoFiler\Jobs\CleanOrphanedUploads;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->deleter = $this->mock(FileDeleter::class);
    Storage::fake('public');
    Log::spy();
});

it('returns early when cleanup is disabled', function () {
    config()->set('auto-filer.cleanup.enabled', false);

    $job = new CleanOrphanedUploads;
    $job->handle($this->deleter);

    $this->deleter->shouldNotHaveReceived('delete');
    Log::shouldNotHaveReceived('info');
});

it('deletes files older than threshold in live mode', function () {
    config()->set('auto-filer.cleanup.enabled', true);
    config()->set('auto-filer.cleanup.dry_run', false);
    config()->set('auto-filer.cleanup.threshold_hours', 24);
    config()->set('auto-filer.temp_directory', 'uploads/temp');
    config()->set('auto-filer.disk', 'public');

    // Create old file
    Storage::disk('public')->put('uploads/temp/old-file.jpg', 'content');
    Storage::disk('public')->put('uploads/temp/new-file.jpg', 'content');

    Storage::shouldReceive('disk->files')
        ->once()
        ->with('uploads/temp')
        ->andReturn([
            'uploads/temp/old-file.jpg',
            'uploads/temp/new-file.jpg',
        ]);

    // Mock file timestamps
    Storage::shouldReceive('disk->lastModified')
        ->with('uploads/temp/old-file.jpg')
        ->andReturn(now()->subHours(25)->timestamp);

    Storage::shouldReceive('disk->lastModified')
        ->with('uploads/temp/new-file.jpg')
        ->andReturn(now()->subHours(12)->timestamp);

    $this->deleter->shouldReceive('delete')
        ->once()
        ->with('uploads/temp/old-file.jpg');

    $job = new CleanOrphanedUploads;
    $job->handle($this->deleter);

    Log::shouldHaveReceived('info')
        ->with('Orphaned file cleanup completed', [
            'path' => 'uploads/temp',
            'deleted' => 1,
            'dry_run' => false,
        ]);
});

it('logs files that would be deleted in dry run mode', function () {
    config()->set('auto-filer.cleanup.enabled', true);
    config()->set('auto-filer.cleanup.dry_run', true);
    config()->set('auto-filer.cleanup.threshold_hours', 24);
    config()->set('auto-filer.temp_directory', 'uploads/temp');
    config()->set('auto-filer.disk', 'public');

    Storage::disk('public')->put('uploads/temp/old-file.jpg', 'content');

    Storage::shouldReceive('disk->files')
        ->once()
        ->with('uploads/temp')
        ->andReturn(['uploads/temp/old-file.jpg']);

    Storage::shouldReceive('disk->lastModified')
        ->with('uploads/temp/old-file.jpg')
        ->andReturn(now()->subHours(25)->timestamp);

    $this->deleter->shouldNotReceive('delete');

    $job = new CleanOrphanedUploads;
    $job->handle($this->deleter);

    Log::shouldHaveReceived('info')
        ->with('Would delete orphaned file: uploads/temp/old-file.jpg');

    Log::shouldHaveReceived('info')
        ->with('Orphaned file cleanup completed', [
            'path' => 'uploads/temp',
            'deleted' => 1,
            'dry_run' => true,
        ]);
});

it('handles empty directory', function () {
    config()->set('auto-filer.cleanup.enabled', true);
    config()->set('auto-filer.temp_directory', 'uploads/temp');
    config()->set('auto-filer.disk', 'public');

    $job = new CleanOrphanedUploads;
    $job->handle($this->deleter);

    Log::shouldHaveReceived('info')
        ->with('Orphaned file cleanup completed', [
            'path' => 'uploads/temp',
            'deleted' => 0,
            'dry_run' => true,
        ]);
});

it('generates unique job ID based on path', function () {
    config()->set('auto-filer.temp_directory', 'uploads/temp');

    $job = new CleanOrphanedUploads;
    $expectedId = 'cleanup_'.hash('sha256', 'uploads/temp');

    expect($job->uniqueId())->toBe($expectedId);
});

it('uses configured queue connection and queue', function () {
    config()->set('auto-filer.queue_connection', 'redis');
    config()->set('auto-filer.queue', 'files');

    $job = new CleanOrphanedUploads;

    expect($job->connection)->toBe('redis');
    expect($job->queue)->toBe('files');
});
