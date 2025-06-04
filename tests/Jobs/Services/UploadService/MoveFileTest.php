<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService;

use Illuminate\Support\Facades\Storage;

/**
 * Tests UploadService moveFile method.
 *
 * @covers \christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService
 */
it('moves the file and returns the new path', function () {
    $path = 'uploads/test.txt';

    Storage::disk($this->disk)->put($path, 'test file content');

    $location = $this->service->moveFile($path, 'test_dir');

    expect(Storage::disk($this->disk)->exists($location))->toBeTrue();
});
