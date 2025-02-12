<?php

namespace christopheraseidl\HasUploads\Tests\UploadServiceTests;

use christopheraseidl\HasUploads\Facades\UploadService;
use Illuminate\Support\Facades\Storage;

it('deletes a directory', function () {
    $dir = 'delete-me';

    Storage::disk($this->disk)->makeDirectory($dir);

    expect(Storage::disk($this->disk)->exists($dir))->toBeTrue();

    UploadService::deleteDirectory($dir);

    expect(Storage::disk($this->disk)->exists($dir))->toBeFalse();
});

it('deletes the model uploads directory', function () {
    Storage::disk($this->disk)->makeDirectory($this->model->getUploadPath());

    expect(Storage::disk($this->disk)->exists($this->model->getUploadPath()))->toBeTrue();

    UploadService::deleteDirectory($this->model->getUploadPath());

    expect(Storage::disk($this->disk)->exists($this->model->getUploadPath()))->toBeFalse();
});
