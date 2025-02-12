<?php

namespace christopheraseidl\HasUploads\Tests\UploadServiceTests;

use christopheraseidl\HasUploads\Facades\UploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('moves the file and returns the new path', function () {
    $file = UploadedFile::fake()->create('fake.txt', 100);

    $oldPath = UploadService::storeFile($this->model, $file, 'documents');

    $newPath = UploadService::moveFile($oldPath, 'newdir');

    expect($newPath)->toBe('newdir/'.pathinfo($oldPath, PATHINFO_BASENAME))
        ->and(Storage::disk($this->disk)->exists($newPath))->toBeTrue();
});
