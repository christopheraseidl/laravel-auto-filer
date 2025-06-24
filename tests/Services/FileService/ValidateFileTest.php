<?php

namespace christopheraseidl\ModelFiler\Tests\Services\FileService;

use Illuminate\Http\UploadedFile;

/**
 * Tests FileService validateFile method.
 *
 * @covers \christopheraseidl\ModelFiler\Tests\Jobs\Services\FileService
 */
beforeEach(function () {
    config()->set('model-filer.max_size', 500);
    config()->set('model-filer.mimes', ['pdf']);
});

it('is happy when the file size and mime type are correct', function () {
    $file = UploadedFile::fake()->create('document.pdf', 500);

    $this->service->validateFile($file);
})->throwsNoExceptions();

it('throws an exception when the file is too big', function () {
    $file = UploadedFile::fake()->create('document.pdf', 501);

    $this->service->validateFile($file);
})->throws(\Exception::class, 'File size exceeds maximum allowed (500KB).');

it('throws an exception for an un-supported mime type', function () {
    $file = UploadedFile::fake()->create('image.notallowed', 500);

    $this->service->validateFile($file);
})->throws(\Exception::class, 'Invalid file type.');
