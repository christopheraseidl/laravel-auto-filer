<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services;

use christopheraseidl\HasUploads\Services\UploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = new UploadService;
    $this->reflection = new \ReflectionClass($this->service);
    config()->set('has-uploads.max_size', 500);
});

it('implements the UploadService interface', function () {
    expect($this->reflection->implementsInterface('christopheraseidl\HasUploads\Contracts\UploadService'))->toBeTrue();
});

it('uses the AttemptsFileMoves trait', function () {
    $traits = $this->reflection->getTraitNames();

    expect($traits)->toHaveCount(1)
        ->and($traits[0])->toBe('christopheraseidl\HasUploads\Traits\AttemptsFileMoves');
});

test('the getDisk() method returns the correct string value', function () {
    $test_value = 'test_disk';
    config()->set('has-uploads.disk', $test_value);

    expect($this->service->getDisk())->toBe($test_value);
});

test('the getPath() method returns the correct string value', function () {
    $test_value = 'test/path';
    config()->set('has-uploads.path', $test_value);

    expect($this->service->getPath())->toBe($test_value);
});

test('the storeFile() method stores an uploaded file at the correct location', function (?string $assetType) {
    $file = UploadedFile::fake()->create('image.png', 100);

    $newLocation = $this->service->storeFile($this->model, $file, $assetType);

    expect(Storage::disk($this->disk)->exists($newLocation))->toBeTrue();

})->with([
    [''],
    ['images'],
]);

test('the validateUpload() method is happy with a valid upload', function () {
    $file = UploadedFile::fake()->create('image.png', 500);

    $this->service->validateUpload($file);
})->throwsNoExceptions();

test('the validateUpload() method throws an exception if the file is larger than the max size', function () {
    $file = UploadedFile::fake()->create('image.png', 501);

    $this->service->validateUpload($file);
})->throws(\Exception::class, 'File size exceeds maximum allowed (500KB).');

test('the validateUpload() method throws an exception if the file type is not allowed', function () {
    $file = UploadedFile::fake()->create('image.notallowed', 500);

    $this->service->validateUpload($file);
})->throws(\Exception::class, 'Invalid file type.');

test('the moveFile() method moves a file to the correct path and returns it as a string', function () {
    $file = UploadedFile::fake()->create('image.png', 500);
    $location = Storage::disk($this->disk)->putFile('', $file);

    $location = $this->service->moveFile($location, 'test_dir');

    expect(Storage::disk($this->disk)->exists($location))->toBeTrue();
});
