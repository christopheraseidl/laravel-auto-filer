<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

/**
 * Tests FileMover performUndo method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
beforeEach(function () {
    $this->oldPath = 'old/path/to/file.jpg';
    $this->newPath = 'new/path/to/file.jpg';
});

it('rolls back a move operation', function () {
    // File has been copied to new path
    $this->mover->shouldReceive('fileExists')
        ->once()
        ->with($this->disk, $this->newPath)
        ->andReturnTrue();

    // File does not exist at old path
    $this->mover->shouldReceive('fileExists')
        ->once()
        ->with($this->disk, $this->oldPath)
        ->andReturnFalse();

    // Copy the file back to the old path
    $this->mover->shouldReceive('copyFile')
        ->once()
        ->with($this->disk, $this->newPath, $this->oldPath);

    // File now exists at old path
    $this->mover->shouldReceive('fileExists')
        ->once()
        ->with($this->disk, $this->oldPath)
        ->andReturnTrue();

    // Delete the file at new path
    $this->mover->shouldReceive('deleteFile')
        ->once()
        ->with($this->disk, $this->newPath);

    $this->mover->performUndo($this->disk, $this->oldPath, $this->newPath);
});

it('does nothing if the file marked for deletion does not exist', function () {
    $this->mover->shouldReceive('fileExists')
        ->once()
        ->with($this->disk, $this->newPath)
        ->andReturnFalse();

    $this->mover->performUndo($this->disk, $this->oldPath, $this->newPath);

    // If we get here without exception, the test passes.
    expect(true)->toBeTrue();
});

it('throws an exception when it fails to restore the file to its original location', function () {
    // File has been copied to new path
    $this->mover->shouldReceive('fileExists')
        ->once()
        ->with($this->disk, $this->newPath)
        ->andReturnTrue();

    // File does not exist at old path
    $this->mover->shouldReceive('fileExists')
        ->twice()
        ->with($this->disk, $this->oldPath)
        ->andReturnFalse();

    // Copy the file back to the old path
    $this->mover->shouldReceive('copyFile')
        ->once()
        ->with($this->disk, $this->newPath, $this->oldPath);

    expect(fn () => $this->mover->performUndo($this->disk, $this->oldPath, $this->newPath))
        ->toThrow(\Exception::class, 'Failed to restore file to original location.');
});
