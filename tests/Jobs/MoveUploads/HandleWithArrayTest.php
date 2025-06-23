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
 * Tests the MoveUploads handle method with array (multiple file) attributes.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\MoveUploads
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
        'file1.text',
        'file2.pdf',
        'file3.doc',
        'file3.docx',
    ];

    $this->files = array_map(function ($file) {
        $path = "old_path/{$file}";

        Storage::disk($this->disk)->put($path, 'test file content');

        return $path;
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
        expect(Storage::disk($this->disk)->exists($file))->toBeTrue();
        expect($file)->toStartWith($this->newDir);
    }
});

it('handles null array attribute gracefully', function () {
    $this->model->array = null;
    $this->model->saveQuietly();

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect($this->model->array)->toBeNull();
    expect(count($this->model->refresh()->array))->toBe(4);
});

it('handles empty array attribute gracefully', function () {
    $this->model->array = [];
    $this->model->saveQuietly();

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect($this->model->array)->toBeEmpty();
    expect(count($this->model->refresh()->array))->toBe(4);
});

it('broadcasts failure event when moving array fails', function () {
    Storage::shouldReceive('disk')->with($this->disk)->andReturnSelf();
    Storage::shouldReceive('copy')
        ->andThrow(new \Exception('File move failed.'));

    expect(fn () => $this->job->handle())
        ->toThrow(\Exception::class, 'Failed to move file after 3 attempts.');

    Event::assertDispatched(FileOperationFailed::class, function ($event) {
        return $event->exception->getMessage() === 'Failed to move file after 3 attempts.';
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
