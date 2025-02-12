<?php

namespace christopheraseidl\HasUploads\Tests\UploadServiceTests;

use christopheraseidl\HasUploads\Facades\UploadService;

it('gets the disk', function () {
    config()->set('has-uploads.disk', 'public');

    $disk = UploadService::getDisk();

    expect($disk)->toBe('public');
});
