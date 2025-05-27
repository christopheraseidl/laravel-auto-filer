<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\MoveUploads;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use christopheraseidl\HasUploads\Jobs\MoveUploads;
use christopheraseidl\HasUploads\Payloads\Contracts\MoveUploads as MoveUploadsPayload;
use christopheraseidl\HasUploads\Payloads\ModelAware;
use christopheraseidl\HasUploads\Tests\TestModels\TestModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

/**
 * Tests the MoveUploads handle method with array (multiple file) attributes.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\MoveUploads
 */
class TestArrayMoveUploadsPayload extends ModelAware implements MoveUploadsPayload
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return true;
    }
}

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

    $this->newDir = 'test_models/1/images';

    $payload = new TestArrayMoveUploadsPayload(
        TestModel::class,
        1,
        'array',
        'images',
        OperationType::Delete,
        OperationScope::File,
        $this->disk,
        $this->files,
        $this->newDir
    );

    $this->job = new MoveUploads($payload);
});

it('moves an array of files to the new path and updates the model with the new location', function () {
    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeTrue();
    }

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeFalse();
    }

    foreach ($this->model->refresh()->array as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeTrue();
    }
});

it('respects custom upload path for array from type parameter', function () {
    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    foreach ($this->model->refresh()->array as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeTrue()
            ->and($file)->toStartWith($this->newDir);
    }
});

it('handles null array attribute gracefully', function () {
    $this->model->array = null;
    $this->model->saveQuietly();

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect($this->model->array)->toBeNull()
        ->and(count($this->model->refresh()->array))->toBe(4);
});

it('handles empty array attribute gracefully', function () {
    $this->model->array = [];
    $this->model->saveQuietly();

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect($this->model->array)->toBeEmpty()
        ->and(count($this->model->refresh()->array))->toBe(4);
});

it('broadcasts failure event when moving array fails', function () {
    $diskMock = \Mockery::mock(Storage::disk($this->disk))->makePartial();
    $diskMock
        ->shouldReceive('move')
        ->andThrow(new \Exception('File move failed.'));

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->andReturn($diskMock);

    $this->job->handle();

    Event::assertDispatched(FileOperationFailed::class, function ($event) {
        return $event->exception->getMessage() === 'File move failed.';
    });
});

it('saves model array changes quietly', function () {
    $modelMock = \Mockery::mock($this->model)->makePartial();

    $modelMock->expects('saveQuietly')
        ->once()
        ->andReturnSelf();

    $payloadMock = \Mockery::mock($this->job->getPayload())->makePartial();
    $payloadMock
        ->shouldReceive('resolveModel')
        ->andReturn($modelMock);

    $this->job = new MoveUploads($payloadMock);

    $this->job->handle();
});
