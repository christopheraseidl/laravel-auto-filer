<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Tests UploadService storeFile() method.
 *
 * @covers \christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService
 */
it('stores the file at the correct location', function (string $assetType) {
    $file = UploadedFile::fake()->create('image.png', 100);

    $newLocation = $this->service->storeFile($this->model, $file, $assetType);

    expect(Storage::disk($this->disk)->exists($newLocation))->toBeTrue();
})->with([
    [''],
    ['images'],
]);
