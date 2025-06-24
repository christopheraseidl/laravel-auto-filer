<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\DeleteUploadDirectory;

use christopheraseidl\ModelFiler\Jobs\Contracts\FileDeleter;

/**
 * Tests the DeleteUploadDirectory executeDeletion method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\DeleteUploadDirectory
 */
it('executes directory deletion', function () {
    $deleterService = $this->mock(FileDeleter::class);
    $deleterService->shouldReceive('attemptDelete')
        ->once()
        ->with($this->disk, $this->path)
        ->andReturnTrue();

    $this->deleter->shouldReceive('getDeleter')
        ->once()
        ->andReturn($deleterService);

    $this->deleter->executeDeletion();
});
