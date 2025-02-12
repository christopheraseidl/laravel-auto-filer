<?php

namespace christopheraseidl\HasUploads\Tests\UploadServiceTests;

use christopheraseidl\HasUploads\Support\UploadService;
use christopheraseidl\Reflect\Reflect;
use Exception;
use Illuminate\Http\UploadedFile;

it('is happy when the file size and mime type are correct', function () {
    config()->set('has-uploads.max_size', 5120);

    config()->set('has-uploads.mimes', ['pdf']);

    $file = UploadedFile::fake()->create('happy-file.pdf', 5120);

    expect(fn () => Reflect::on(new UploadService)->validateUpload($file))
        ->not->toThrow(Exception::class, "File size exceeds maximum allowed ({config('has-uploads.max_size')}KB).")
        ->not->toThrow(Exception::class, 'Invalid file type');
});

it('throws an exception when the file is too big', function () {
    config()->set('has-uploads.max_size', 5120);

    $file = UploadedFile::fake()->image('too-big.png')->size(5121);

    expect(fn () => Reflect::on(new UploadService)->validateUpload($file))->toThrow(Exception::class, "File size exceeds maximum allowed ({config('has-uploads.max_size')}KB).");
});

it('throws an exception for an un-supported mime type', function () {
    config()->set('has-uploads.mimes', ['pdf']);

    $file = UploadedFile::fake()->create('wrong-mime.jpg', 100);

    expect(fn () => Reflect::on(new UploadService)->validateUpload($file))->toThrow(Exception::class, 'Invalid file type.');
});
