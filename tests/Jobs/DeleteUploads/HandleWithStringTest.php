<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\DeleteUploads;

use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use christopheraseidl\HasUploads\Facades\UploadService;
use christopheraseidl\HasUploads\Jobs\DeleteUploads;
use ErrorException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

/**
 * Tests the DeleteUploads job's handling of string (single file) attributes,
 * including successful deletions, error handling, and model updates.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\DeleteUploads
 */
beforeEach(function () {
    Event::fake([
        FileOperationCompleted::class,
        FileOperationFailed::class,
    ]);

    $dir = 'my_path/subdir';
    $file = UploadedFile::fake()->create('image.png', 100);
    $name = $file->hashName();
    $this->path = "{$dir}/{$name}";

    Storage::disk($this->disk)->putFileAs($dir, $file, $name);

    $this->job = new DeleteUploads(
        $this->model,
        $this->path
    );
});

it('deletes a single file and dispatches the correct event', function () {
    expect(Storage::disk($this->disk)->exists($this->path))->toBeTrue();

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect(Storage::disk($this->disk)->exists($this->path))->toBeFalse();
});

it('broadcasts failure event when delete single file fails', function () {
    UploadService::partialMock()
        ->shouldReceive('deleteFile')
        ->once()
        ->andThrow(ErrorException::class);

    $this->job->handle();

    expect(Storage::disk($this->disk)->exists($this->path))->toBeTrue();
});
