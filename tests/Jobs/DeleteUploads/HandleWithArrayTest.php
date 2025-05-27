<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\DeleteUploads;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use christopheraseidl\HasUploads\Jobs\DeleteUploads;
use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploads as DeleteUploadsPayload;
use christopheraseidl\HasUploads\Payloads\ModelAware;
use christopheraseidl\HasUploads\Tests\TestModels\TestModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

/**
 * Tests the DeleteUploads handle method with array (multiple file) attributes.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\DeleteUploads
 */
class TestArrayDeleteUploadsPayload extends ModelAware implements DeleteUploadsPayload
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
        $path = 'test_models/1';
        Storage::disk($this->disk)->putFileAs($path, $file, $name);

        return "{$path}/{$name}";
    }, $this->files);

    $payload = new TestArrayDeleteUploadsPayload(
        TestModel::class,
        1,
        'array',
        'documents',
        OperationType::Delete,
        OperationScope::File,
        $this->disk,
        $this->files
    );

    $this->job = new DeleteUploads($payload);
});

it('deletes an array of files and dispatches the completion event when enabled', function () {
    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeTrue();
    }

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeFalse();
    }
});

it('deletes only the provided files and handles a sparse array gracefully', function () {
    $files = $this->files;
    unset($files[0]);
    unset($files[2]);

    $payload = new TestArrayDeleteUploadsPayload(
        TestModel::class,
        1,
        'array',
        'documents',
        OperationType::Delete,
        OperationScope::File,
        $this->disk,
        $files
    );

    $job = new DeleteUploads($payload);

    $job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect(Storage::disk($this->disk)->exists($this->files[0]))->toBeTrue()
        ->and(Storage::disk($this->disk)->exists($this->files[2]))->toBeTrue()
        ->and(Storage::disk($this->disk)->exists($this->files[1]))->toBeFalse()
        ->and(Storage::disk($this->disk)->exists($this->files[3]))->toBeFalse();
});

it('handles null array attribute gracefully', function () {
    $this->model->array = null;
    $this->model->saveQuietly();

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeFalse();
    }
});

it('handles empty array attribute gracefully', function () {
    $this->model->array = [];
    $this->model->saveQuietly();

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    foreach ($this->files as $file) {
        expect(Storage::disk($this->disk)->exists($file))->toBeFalse();
    }
});

it('broadcasts a failure event when deleting an array of files fails', function () {
    $diskMock = \Mockery::mock(Storage::disk($this->disk))->makePartial();
    $diskMock->shouldReceive('delete')
        ->andThrow(new \Exception('File deletion failed'));

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->andReturn($diskMock);

    $this->job->handle();

    Event::assertDispatched(FileOperationFailed::class);
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

    $this->job = new DeleteUploads($payloadMock);

    $this->job->handle();
});
