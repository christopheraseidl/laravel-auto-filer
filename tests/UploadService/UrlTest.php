<?php

namespace christopheraseidl\HasUploads\Tests\UploadServiceTests;

use christopheraseidl\HasUploads\Facades\UploadService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    config()->set('has-uploads.path', 'uploads');
});

it('returns the file URL', function () {
    $file = UploadedFile::fake()->create('fake.txt', 100);

    $path = UploadService::storeFile($this->model, $file, 'documents');

    $name = basename($path);

    $url = UploadService::url($path);

    expect($url)->toBe("/storage/uploads/test_models/{$this->model->id}/documents/$name");
});
