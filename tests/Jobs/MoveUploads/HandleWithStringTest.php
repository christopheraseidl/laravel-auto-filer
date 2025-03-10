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
 * Tests the MoveUploads job's handling of string (single file) attributes,
 * including successful moves, custom paths, error handling, and model updates.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\MoveUploads
 */
class TestStringMoveUploadsPayload extends ModelAware implements MoveUploadsPayload
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

    $file = UploadedFile::fake()->create('image.png', 100);
    $name = $file->hashName();
    $path = 'old_path';
    $this->oldPath = "{$path}/{$name}";
    Storage::disk($this->disk)->putFileAs($path, $file, $name);

    $this->model->string = $this->oldPath;
    $this->model->saveQuietly();

    $this->newDir = 'test_models/1/images';
    $this->newPath = "{$this->newDir}/{$name}";

    $payload = new TestStringMoveUploadsPayload(
        TestModel::class,
        1,
        'string',
        'images',
        OperationType::Move,
        OperationScope::File,
        $this->disk,
        [$this->oldPath],
        $this->newDir
    );

    $this->job = new MoveUploads($payload);
});

it('moves a single file to the new path and updates the model with the new location', function () {
    expect(Storage::disk($this->disk)->exists($this->oldPath))->toBeTrue();

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect(Storage::disk($this->disk)->exists($this->oldPath))->toBeFalse()
        ->and(Storage::disk($this->disk)->exists($this->newPath))->toBeTrue()
        ->and($this->model->refresh()->string)->toBe($this->newPath);
});

it('respects custom upload path for string from type parameter', function () {
    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect(Storage::disk($this->disk)->exists($this->newPath))->toBeTrue()
        ->and($this->model->refresh()->string)->toBe($this->newPath);
});

it('handles null model string attribute gracefully', function () {
    $this->model->string = null;
    $this->model->saveQuietly();

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect($this->model->refresh()->string)->toBe($this->newPath);
});

it('handles empty model string attribute gracefully', function () {
    $this->model->string = '';
    $this->model->saveQuietly();

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect($this->model->refresh()->string)->toBe($this->newPath);
});

it('broadcasts failure event when single file move fails', function () {
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

it('saves model string changes quietly', function () {
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
