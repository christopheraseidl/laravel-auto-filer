<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\CleanOrphanedUploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Tests the CleanOrphanedUploads getLastModified method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\CleanOrphanedUploads
 */
beforeEach(function () {
    config()->set('model-filer.path', 'uploads');

    $upload = UploadedFile::fake()->create('file1.txt', 100);
    $name = $upload->hashName();
    $this->file = "{$this->path}/{$name}";

    Storage::disk($this->disk)->putFileAs($this->path, $upload, $name);
});

it('returns the expected last modified value', function () {
    $lastModifiedFromStorage = Storage::disk($this->disk)->lastModified($this->file);
    $lastModifiedFromService = $this->cleaner->getLastModified($this->file);

    expect($lastModifiedFromService)->toBeInstanceOf(\DateTimeInterface::class);
    expect($lastModifiedFromService->getTimestamp())->toBe($lastModifiedFromStorage);
});
