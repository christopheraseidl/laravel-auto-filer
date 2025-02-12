<?php

namespace christopheraseidl\HasUploads\Tests\UploadServiceTests;

use christopheraseidl\HasUploads\Facades\UploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('correctly deletes an uploaded file', function () {
    $file = UploadedFile::fake()->image('fake.png', 100);

    $path = UploadService::storeFile($this->model, $file, 'images');

    expect(Storage::disk($this->disk)->exists($path))->toBeTrue();

    $result = UploadService::deleteFile($path);

    expect($result)->toBeTrue();
    expect(Storage::disk($this->disk)->exists($path))->toBeFalse();
});
