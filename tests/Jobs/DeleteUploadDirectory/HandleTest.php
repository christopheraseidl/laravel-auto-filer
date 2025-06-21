<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\DeleteUploadDirectory;

use christopheraseidl\ModelFiler\Events\FileOperationCompleted;
use christopheraseidl\ModelFiler\Events\FileOperationFailed;
use christopheraseidl\ModelFiler\Jobs\Contracts\FileDeleter;
use Illuminate\Support\Facades\Event;

/**
 * Tests the DeleteUploadDirectory handle method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\DeleteUploadDirectory
 */
beforeEach(function () {
    Event::fake([
        FileOperationCompleted::class,
        FileOperationFailed::class,
    ]);
});

it('deletes the correct directory and broadcasts completion event', function () {
    $deleterService = $this->mock(FileDeleter::class);
    $deleterService->shouldReceive('attemptDelete')
        ->once()
        ->with($this->disk, $this->path)
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

it('broadcasts failure event when exception is thrown', function () {
    $this->deleter->shouldReceive('getDeleter')
        ->once()
        ->andThrow(new \Exception('Disk error'));

    $this->payload->shouldReceive('shouldBroadcastIndividualEvents')
        ->once()
        ->andReturnTrue();

    $this->deleter->handle();

    Event::assertDispatched(FileOperationFailed::class);
});
