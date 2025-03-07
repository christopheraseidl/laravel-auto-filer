<?php

/**
 * Tests the CleanOrphanedUploads getLastModified method.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\CleanOrphanedUploads
 */

use christopheraseidl\HasUploads\Jobs\CleanOrphanedUploads;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('has-uploads.path', 'uploads');

    $upload = UploadedFile::fake()->create('file1.txt', 100);
    $name = $upload->hashName();
    $this->file = "{$this->path}/{$name}";

    Storage::disk($this->disk)->putFileAs($this->path, $upload, $name);
});

it('returns the expected last modified value', function () {
    $lastModifiedFromStorage = Storage::disk($this->disk)->lastModified($this->file);
    $lastModifiedFromService = $this->cleaner->getLastModified($this->file);

    expect($lastModifiedFromService)->toBeInstanceOf(DateTimeInterface::class)
        ->and($lastModifiedFromService->getTimestamp())->toBe($lastModifiedFromStorage);
});
