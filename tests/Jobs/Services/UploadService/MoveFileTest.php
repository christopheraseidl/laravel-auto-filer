<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Tests UploadService moveFile method.
 *
 * @covers \christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService
 */
it('moves the file and returns the new path', function () {
    $file = UploadedFile::fake()->create('image.png', 500);
    $location = Storage::disk($this->disk)->putFile('', $file);

    $location = $this->service->moveFile($location, 'test_dir');

    expect(Storage::disk($this->disk)->exists($location))->toBeTrue();
});
