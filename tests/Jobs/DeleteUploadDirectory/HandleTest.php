<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\DeleteUploadDirectory;

use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

/**
 * Tests the DeleteUploadDirectory handle method.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\DeleteUploadDirectory
 */
beforeEach(function () {
    Event::fake([
        FileOperationCompleted::class,
        FileOperationFailed::class,
    ]);

    $this->dir = 'test_models/1';
    $this->file = $this->dir.'/my_file.txt';
    Storage::disk($this->disk)->put($this->file, 'content');

    $this->model->string = $this->file;
    $this->model->saveQuietly();
});

it('deletes the correct directory and broadcasts completion event', function () {
    expect(Storage::disk($this->disk)->exists($this->file))->toBeTrue();
    expect(Storage::disk($this->disk)->exists($this->dir))->toBeTrue();

    $this->job->handle();

    expect(Storage::disk($this->disk)->exists($this->file))->toBeFalse();
    expect(Storage::disk($this->disk)->exists($this->dir))->toBeFalse();

    Event::assertDispatched(FileOperationCompleted::class);
});

it('broadcasts failure event when exception is thrown', function () {
    Storage::shouldReceive($this->disk)->andThrow(new \Exception('Disk error'));

    $this->job->handle();

    Event::assertDispatched(FileOperationFailed::class);
});
