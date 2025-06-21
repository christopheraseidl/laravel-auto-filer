<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\CleanOrphanedUploads;

use Illuminate\Support\Facades\Storage;

/**
 * Tests the CleanOrphanedUploads getLastModified method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\CleanOrphanedUploads
 */
beforeEach(function () {
    $this->file = 'uploads/old_file.txt';

    Storage::disk($this->disk)->put($this->file, 'content');
});

it('returns the expected last modified value', function () {
    $lastModifiedFromStorage = Storage::disk($this->disk)->lastModified($this->file);
    $lastModifiedFromService = $this->cleaner->getLastModified($this->file);

    expect($lastModifiedFromService)->toBeInstanceOf(\DateTimeInterface::class);
    expect($lastModifiedFromService->getTimestamp())->toBe($lastModifiedFromStorage);
});
