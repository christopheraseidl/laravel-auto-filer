<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService;

use Illuminate\Http\UploadedFile;

/**
 * Tests UploadService validateUpload() method.
 *
 * @covers \christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService
 */
beforeEach(function () {
    config()->set('has-uploads.max_size', 500);
    config()->set('has-uploads.mimes', ['pdf']);
});

it('is happy when the file size and mime type are correct', function () {
    $file = UploadedFile::fake()->create('document.pdf', 500);

    $this->service->validateUpload($file);
})->throwsNoExceptions();

it('throws an exception when the file is too big', function () {
    $file = UploadedFile::fake()->create('document.pdf', 501);

    $this->service->validateUpload($file);
})->throws(\Exception::class, 'File size exceeds maximum allowed (500KB).');

it('throws an exception for an un-supported mime type', function () {
    $file = UploadedFile::fake()->create('image.notallowed', 500);

    $this->service->validateUpload($file);
})->throws(\Exception::class, 'Invalid file type.');
