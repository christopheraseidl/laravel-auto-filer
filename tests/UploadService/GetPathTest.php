<?php

namespace christopheraseidl\HasUploads\Tests\UploadServiceTests;

use christopheraseidl\HasUploads\Facades\UploadService;

beforeEach(function () {
    config()->set('has-uploads.path', 'uploads');
});

it('gets the upload path', function () {
    $path = UploadService::getPath();

    expect($path)->toBe('uploads');
});
