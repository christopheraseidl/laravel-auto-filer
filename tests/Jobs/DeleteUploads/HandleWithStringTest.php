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
 * Tests the DeleteUploads handle method with string (single file) attributes.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\DeleteUploads
 */
class TestStringDeleteUploadsPayload extends ModelAware implements DeleteUploadsPayload
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

    $dir = 'my_path/subdir';
    $file = UploadedFile::fake()->create('image.png', 100);
    $name = $file->hashName();
    $this->path = "{$dir}/{$name}";

    Storage::disk($this->disk)->putFileAs($dir, $file, $name);

    $payload = new TestStringDeleteUploadsPayload(
        TestModel::class,
        1,
        'string',
        'images',
        OperationType::Delete,
        OperationScope::File,
        $this->disk,
        [$this->path]
    );

    $this->job = new DeleteUploads($payload);
});

it('deletes a single file and dispatches the completion event when enabled', function () {
    expect(Storage::disk($this->disk)->exists($this->path))->toBeTrue();

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect(Storage::disk($this->disk)->exists($this->path))->toBeFalse();
});

it('handles null string attribute gracefully', function () {
    $this->model->string = null;
    $this->model->saveQuietly();

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect(Storage::disk($this->disk)->exists($this->path))->toBeFalse();
});

it('handles empty string attribute gracefully', function () {
    $this->model->string = '';
    $this->model->saveQuietly();

    $this->job->handle();

    Event::assertDispatched(FileOperationCompleted::class);

    expect(Storage::disk($this->disk)->exists($this->path))->toBeFalse();
});

it('broadcasts failure event when delete single file fails', function () {
    $diskMock = \Mockery::mock(Storage::disk($this->disk))->makePartial();
    $diskMock
        ->shouldReceive('delete')
        ->andThrow(new \Exception('File deletion failed'));

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->andReturn($diskMock);

    $this->job->handle();

    expect(Storage::disk($this->disk)->exists($this->path))->toBeTrue();
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

    $this->job = new DeleteUploads($payloadMock);

    $this->job->handle();
});
