<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

/**
 * Tests FileMover performMove method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('moves a file and returns the new path', function () {
    $this->mover->shouldReceive('generateUniqueFileName')
        ->once()
        ->with($this->disk, $this->newPath)
        ->andReturn($this->newPath);
    $this->mover->shouldReceive('copyFile')
        ->once()
        ->with($this->disk, $this->oldPath, $this->newPath);
    $this->mover->shouldReceive('validateCopiedFile')
        ->once()
        ->with($this->disk, $this->newPath);
    $this->mover->shouldReceive('deleteFile')
        ->once()
        ->with($this->disk, $this->oldPath);
    $this->mover->shouldReceive('commitMovedFile')
        ->once()
        ->with($this->oldPath, $this->newPath)
        ->andReturn($this->newPath);

    $this->breaker->shouldReceive('recordSuccess')->once();

    $result = $this->mover->performMove($this->disk, $this->oldPath, $this->newPath);

    expect($result)->toBe($this->newPath);
});
