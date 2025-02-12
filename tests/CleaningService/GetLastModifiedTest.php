<?php

/**
 * Tests the CleaningService getLastModified method.
 *
 * @covers \christopheraseidl\HasUploads\Support\CleaningService
 */

use christopheraseidl\HasUploads\Support\CleaningService;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('has-uploads.path', 'uploads');

    $upload = UploadedFile::fake()->create('file1.txt', 100);
    $name = $upload->hashName();
    $path = config('has-uploads.path');
    $this->file = "{$path}/{$name}";

    Storage::disk($this->disk)->putFileAs($path, $upload, $name);

    $this->cleaner = Reflect::on(new CleaningService);
});

it('returns the expected last modified value', function () {
    $lastModifiedFromStorage = Storage::disk($this->disk)->lastModified($this->file);
    $lastModifiedFromService = $this->cleaner->getLastModified($this->file);

    expect($lastModifiedFromService)->toBeInstanceOf(DateTimeInterface::class)
        ->and($lastModifiedFromService->getTimestamp())->toBe($lastModifiedFromStorage);
});
