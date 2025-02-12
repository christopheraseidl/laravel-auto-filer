<?php

use Carbon\Carbon;
use christopheraseidl\HasUploads\Support\CleaningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Tests the CleaningService clean method.
 *
 * @covers \christopheraseidl\HasUploads\Support\CleaningService
 */
beforeEach(function () {
    config()->set('has-uploads.path', 'uploads');

    $this->files = [
        UploadedFile::fake()->create('file1.txt', 100),
        UploadedFile::fake()->create('file2.pdf', 200),
        UploadedFile::fake()->create('file3.doc', 300),
        UploadedFile::fake()->create('file4.docx', 400),
    ];

    $this->files = array_map(function ($file) {
        $name = $file->hashName();
        $path = config('has-uploads.path');
        Storage::disk($this->disk)->putFileAs($path, $file, $name);

        return "{$path}/{$name}";
    }, $this->files);

    $this->cleaner = new CleaningService;
});

it('only deletes files older than 24 hours', function () {
    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeTrue();
    }

    $this->cleaner->clean();

    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeTrue();
    }

    Carbon::setTestNow(now()->addHours(25));

    $this->cleaner->clean();

    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeFalse();
    }
});
