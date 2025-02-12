<?php

namespace christopheraseidl\HasUploads\Tests\UploadServiceTests;

use christopheraseidl\HasUploads\Facades\UploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores the file', function () {
    $oldName = 'fake.txt';

    $file = UploadedFile::fake()->create($oldName, 100);

    $path = UploadService::storeFile($this->model, $file, 'documents');

    $newName = basename($path);

    expect(Storage::disk($this->disk)->exists($path))->toBeTrue()
        ->and($oldName)->not->toBe($newName);
});
