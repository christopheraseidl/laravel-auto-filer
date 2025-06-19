<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\MoveUploads;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Events\FileOperationCompleted;
use christopheraseidl\ModelFiler\Events\FileOperationFailed;
use christopheraseidl\ModelFiler\Jobs\MoveUploads;
use christopheraseidl\ModelFiler\Payloads\Contracts\MoveUploads as MoveUploadsPayload;
use christopheraseidl\ModelFiler\Payloads\ModelAware;
use christopheraseidl\ModelFiler\Tests\TestModels\TestModel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

/**
 * Tests the MoveUploads handle method with string (single file) attributes.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\MoveUploads
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

    $name = 'test.txt';
    $this->oldPath = "uploads/{$name}";
    $this->newDir = 'test_models/1/images';
    $this->newPath = "{$this->newDir}/{$name}";

    Storage::disk($this->disk)->put($this->oldPath, 'test file content');

    $this->model->string = $this->oldPath;
    $this->model->saveQuietly();

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

    expect(Storage::disk($this->disk)->exists($this->oldPath))->toBeFalse();
    expect(Storage::disk($this->disk)->exists($this->newPath))->toBeTrue();
    expect($this->model->refresh()->string)->toBe($this->newPath);
});

it('respects custom upload path for string from type parameter', function () {
    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect(Storage::disk($this->disk)->exists($this->newPath))->toBeTrue();
    expect($this->model->refresh()->string)->toBe($this->newPath);
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
        ->shouldReceive('copy')
        ->andThrow(new \Exception('File copy failed.'));

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->andReturn($diskMock);

    $this->job->handle();

    Event::assertDispatched(FileOperationFailed::class, function ($event) {
        return $event->exception->getMessage() === 'Failed to move file after 3 attempts.';
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
