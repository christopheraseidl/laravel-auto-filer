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
 * Tests the DeleteUploads job's handling of array (multiple file) attributes,
 * including successful deletions, error handling, and model updates.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\DeleteUploads
 */
beforeEach(function () {
    Event::fake([
        FileOperationCompleted::class,
        FileOperationFailed::class,
    ]);

    $this->files = [
        UploadedFile::fake()->create('file1.text', 100),
        UploadedFile::fake()->create('file2.pdf', 200),
        UploadedFile::fake()->create('file3.doc', 300),
        UploadedFile::fake()->create('file3.docx', 400),
    ];

    $this->files = array_map(function ($file) {
        $name = $file->hashName();
        $path = 'my_path/subdir';
        Storage::disk($this->disk)->putFileAs($path, $file, $name);

        return "{$path}/{$name}";
    }, $this->files);

    $this->job = new DeleteUploads(
        $this->model,
        $this->files
    );
});

it('deletes an array of files and broadcasts the correct event', function () {
    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeTrue();
    }

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeFalse();
    }
});

it('broadcasts failure event when deleting an array of files fails', function () {
    UploadService::partialMock()
        ->shouldReceive('deleteFile')
        ->once()
        ->andThrow(ErrorException::class);

    $this->job->handle();

    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeTrue();
    }
});
