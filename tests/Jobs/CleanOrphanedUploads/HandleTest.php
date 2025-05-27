<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\CleanOrphanedUploads;

use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use Illuminate\Support\Facades\Event;
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
    $this->cleaner->handle();

    expect(Storage::disk($this->disk)->exists($this->oldFile))
        ->toBeFalse()
        ->and(Storage::disk($this->disk)->exists($this->newFile))
        ->toBeTrue();

    Event::assertDispatched(FileOperationCompleted::class);
});

it('broadcasts failure event when exception is thrown', function () {
    Storage::shouldReceive($this->disk)->andThrow(new \Exception('Disk error'));

    $this->cleaner->handle();

    Event::assertDispatched(FileOperationFailed::class);
});
