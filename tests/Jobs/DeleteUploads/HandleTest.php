<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\DeleteUploads;

use christopheraseidl\ModelFiler\Events\FileOperationCompleted;
use christopheraseidl\ModelFiler\Events\FileOperationFailed;
use christopheraseidl\ModelFiler\Jobs\Contracts\FileDeleter;
use christopheraseidl\ModelFiler\Jobs\DeleteUploads;
use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploads as DeleteUploadsPayload;
use christopheraseidl\ModelFiler\Payloads\ModelAware;
use Illuminate\Support\Facades\Event;

/**
 * Tests the DeleteUploads handle method with array (multiple file) attributes.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\DeleteUploads
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
});

it('deletes arrays of files and dispatches the completion event when enabled', function (array $files) {
    $count = count($files);

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn($files);

    $deleterService = $this->mock(FileDeleter::class);
    $deleterService->shouldReceive('attemptDelete')
        ->times($count)
        ->withArgs(function ($disk, $file) use ($files) {
            return $disk === $this->disk && in_array($file, $files);
        })
        ->andReturnTrue();

    $this->deleter->shouldReceive('getDeleter')
        ->times($count)
        ->andReturn($deleterService);

    $this->payload->shouldReceive('shouldBroadcastIndividualEvents')
        ->once()
        ->andReturnTrue();

    $this->deleter->handle();

    Event::assertDispatched(FileOperationCompleted::class);
})->with([
    'multiple_files' => [
        [
            'path/to/image.jpg',
            'path/to/document.txt',
            'path/to/error.log',
            'path/to/avagar.png',
        ],
    ],
    'single_file' => [
        ['just/one/file.txt'],
    ],
]);

it('deletes only the provided files and handles a sparse array gracefully', function () {
    $files = [
        'path/to/image.jpg',
        'path/to/document.txt',
        'path/to/error.log',
        'path/to/avagar.png',
    ];
    unset($files[0]);
    unset($files[2]);
    $count = count($files);

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn($files);

    $deleterService = $this->mock(FileDeleter::class);
    $deleterService->shouldReceive('attemptDelete')
        ->times($count)
        ->withArgs(function ($disk, $file) use ($files) {
            return $disk === $this->disk && in_array($file, $files);
        })
        ->andReturnTrue();

    $this->deleter->shouldReceive('getDeleter')
        ->times($count)
        ->andReturn($deleterService);

    $this->payload->shouldReceive('shouldBroadcastIndividualEvents')
        ->once()
        ->andReturnTrue();

    $this->deleter->handle();

    Event::assertDispatched(FileOperationCompleted::class);
});

it('handles null array attribute gracefully', function () {
    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturnNull();

    $this->deleter->shouldReceive('getDeleter')->never();

    $this->payload->shouldReceive('shouldBroadcastIndividualEvents')
        ->once()
        ->andReturnTrue();

    $this->deleter->handle();

    Event::assertDispatched(FileOperationCompleted::class);
});

it('handles empty array attribute gracefully', function () {
    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturnNull();

    $this->deleter->shouldReceive('getDeleter')->never();

    $this->payload->shouldReceive('shouldBroadcastIndividualEvents')
        ->once()
        ->andReturnTrue();

    $this->deleter->handle();

    Event::assertDispatched(FileOperationCompleted::class);
});

it('handles empty path attribute gracefully', function () {
    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn(['']);

    $deleterService = $this->mock(FileDeleter::class);
    $deleterService->shouldReceive('attemptDelete')
        ->once()
        ->with($this->disk, '')
        ->andReturnTrue();

    $this->deleter->shouldReceive('getDeleter')
        ->once()
        ->andReturn($deleterService);

    $this->payload->shouldReceive('shouldBroadcastIndividualEvents')
        ->once()
        ->andReturnTrue();

    $this->deleter->handle();

    Event::assertDispatched(FileOperationCompleted::class);
});

it('throws an exception and broadcasts a failure event when path attribute is null', function () {
    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn([null]);

    $deleterService = $this->mock(FileDeleter::class);
    $deleterService->shouldReceive('attemptDelete')
        ->never();

    $this->deleter->shouldReceive('getDeleter')
        ->once()
        ->andReturn($deleterService);

    $this->payload->shouldReceive('shouldBroadcastIndividualEvents')
        ->once()
        ->andReturnTrue();

    expect(fn () => $this->deleter->handle())
        ->toThrow(\TypeError::class, 'Argument #2 ($path) must be of type string, null given');

    Event::assertDispatched(FileOperationFailed::class);
});

it('broadcasts a failure event when deleting an array of files fails', function () {
    $files = ['just/one/file.txt'];
    $count = count($files);

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn($files);

    $deleterService = $this->mock(FileDeleter::class);
    $deleterService->shouldReceive('attemptDelete')
        ->once()
        ->andThrow(new \Exception('Disk error'));

    $this->deleter->shouldReceive('getDeleter')
        ->times($count)
        ->andReturn($deleterService);

    $this->payload->shouldReceive('shouldBroadcastIndividualEvents')
        ->once()
        ->andReturnTrue();

    expect(fn () => $this->deleter->handle())
        ->toThrow(\Exception::class, 'Disk error');

    Event::assertDispatched(FileOperationFailed::class);
});
