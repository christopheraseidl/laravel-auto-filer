<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

/**
 * Tests FileMover utility methods behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('can return original path when file does not exist', function () {
    $this->mover->shouldReceive('fileExists')
        ->once()
        ->with($this->disk, $this->oldPath)
        ->andReturnFalse();

    $result = $this->mover->generateUniqueFileName($this->disk, $this->oldPath);

    expect($result)->toBe($this->oldPath);
});

it('can generate unique filename when file exists', function () {
    $this->mover->shouldReceive('fileExists')
        ->with($this->disk, $this->oldPath)
        ->andReturnTrue();

    $this->mover->shouldReceive('fileExists')
        ->with($this->disk, 'uploads/test_1.txt')
        ->andReturnTrue();

    $this->mover->shouldReceive('fileExists')
        ->with($this->disk, 'uploads/test_2.txt')
        ->andReturnTrue();

    $this->mover->shouldReceive('fileExists')
        ->with($this->disk, 'uploads/test_3.txt')
        ->andReturnFalse();

    $result = $this->mover->generateUniqueFileName($this->disk, $this->oldPath);

    expect($result)->toBe('uploads/test_3.txt');
});

it('builds new path from directory and filename', function () {
    $result = $this->mover->buildNewPath($this->newDir, $this->oldPath);

    expect($result)->toBe('new/dir/test.txt');
});
