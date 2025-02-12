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
 * Tests the MoveUploads job's handling of string (single file) attributes,
 * including successful moves, custom paths, error handling, and model updates.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\MoveUploads
 */
beforeEach(function () {
    Event::fake([
        FileOperationCompleted::class,
        FileOperationFailed::class,
    ]);

    $this->file = UploadedFile::fake()->create('image.png', 100);
    $oldDir = 'old_path';
    $this->oldPath = "{$oldDir}/{$this->file->hashName()}";
    Storage::disk($this->disk)->putFileAs($oldDir, $this->file, $this->file->hashName());

    $this->model->string = $this->oldPath;
    $this->model->saveQuietly();
});

it('moves a single file to the new path and updates the model with the new location', function () {
    $job = new MoveUploads(
        $this->model,
        'string'
    );

    $newPath = "test_models/1/{$this->file->hashName()}";

    expect(Storage::disk($this->disk)->exists($this->oldPath))->toBeTrue();

    $job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect(Storage::disk($this->disk)->exists($this->oldPath))->toBeFalse()
        ->and(Storage::disk($this->disk)->exists($newPath))->toBeTrue()
        ->and($this->model->refresh()->string)->toBe($newPath);
});

it('respects custom upload path for string from type parameter', function () {
    $job = new MoveUploads(
        $this->model,
        'string',
        'images'
    );

    $newPath = "test_models/1/images/{$this->file->hashName()}";

    $job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect(Storage::disk($this->disk)->exists($newPath))->toBeTrue()
        ->and($this->model->refresh()->string)->toBe($newPath);
});

it('handles empty model string attribute gracefully', function () {
    $this->model->string = null;
    $this->model->saveQuietly();

    $job = new MoveUploads(
        $this->model,
        'string'
    );

    $job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect($this->model->refresh()->string)->toBeNull();
});

it('broadcasts failure event when single file move fails', function () {
    $job = new MoveUploads(
        $this->model,
        'string'
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

it('saves model string changes quietly', function () {
    $modelSpy = spyModel($this->model);

    $modelSpy->shouldReceive('saveQuietly')
        ->once()
        ->andReturnSelf();

    $job = new MoveUploads(
        $modelSpy,
        'string'
    );

    $job->handle();
});
