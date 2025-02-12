<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\MoveUploads;

use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use christopheraseidl\HasUploads\Facades\UploadService;
use christopheraseidl\HasUploads\Jobs\MoveUploads;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

/**
 * Tests the MoveUploads job's handling of array (multiple file) attributes,
 * including batch file moves, error conditions, and array-specific model updates.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\MoveUploads
 */
beforeEach(function () {
    Event::fake([
        FileOperationCompleted::class,
        FileOperationFailed::class,
    ]);

    $this->files = [
        UploadedFile::fake()->create('file1.text', 100),
        UploadedFile::fake()->create('file2.pdf', 200),
        UploadedFile::fake()->create('file3.doc', 300),
        UploadedFile::fake()->create('file3.docx', 400),
    ];

    $this->files = array_map(function ($file) {
        $name = $file->hashName();
        $path = 'old_path';
        Storage::disk($this->disk)->putFileAs($path, $file, $name);

        return "{$path}/{$name}";
    }, $this->files);

    $this->model->array = $this->files;
    $this->model->saveQuietly();
});

it('moves an array of files to the new path and updates the model with the new location', function () {
    $job = new MoveUploads(
        $this->model,
        'array',
        'documents'
    );

    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeTrue();
    }

    $job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeFalse();
    }

    foreach ($this->model->refresh()->array as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeTrue();
    }
});

it('respects custom upload path for array from type parameter', function () {
    $job = new MoveUploads(
        $this->model,
        'array',
        'images'
    );

    $newBasePath = 'test_models/1/images';

    $job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    foreach ($this->model->refresh()->array as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeTrue()
            ->and($file)->toStartWith($newBasePath);
    }
});

it('handles null array attribute gracefully', function () {
    $this->model->array = null;
    $this->model->saveQuietly();

    $job = new MoveUploads(
        $this->model,
        'string'
    );

    $job->handle();

    Event::assertDispatched(FileOperationCompleted::class);
    expect($this->model->refresh()->array)->toBeNull();
});

it('handles empty array attribute gracefully', function () {
    $this->model->array = [];
    $this->model->saveQuietly();

    $job = new MoveUploads(
        $this->model,
        'string'
    );

    $job->handle();

    Event::assertDispatched(FileOperationCompleted::class);
    expect($this->model->refresh()->array)->toBeEmpty();
});

it('broadcasts failure event when moving array fails', function () {
    $job = new MoveUploads(
        $this->model,
        'array'
    );

    UploadService::partialMock()
        ->shouldReceive('moveFile')
        ->once()
        ->andThrow(new Exception('Failed to move file after {1} attempts.'));

    $job->handle();

    Event::assertDispatched(FileOperationFailed::class, function ($event) {
        return $event->exception->getMessage() === 'Failed to move file after {1} attempts.';
    });
});

it('saves array changes quietly', function () {
    $modelMock = spyModel($this->model);

    $modelMock->expects('saveQuietly')
        ->once()
        ->andReturnSelf();

    $job = new MoveUploads(
        $modelMock,
        'array'
    );

    $job->handle();
});
