<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\FileMover;

use christopheraseidl\HasUploads\Jobs\Services\CircuitBreaker;
use christopheraseidl\HasUploads\Jobs\Services\FileMover;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Facades\Storage;

/**
 * Tests FileMover performUndo method behavior.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Services\FileMover
 */
beforeEach(function () {
    $this->oldPath = 'old/path/to/file.jpg';
    $this->newPath = 'new/path/to/file.jpg';

    $circuitBreaker = $this->mock(CircuitBreaker::class);
    $this->fileMover = Reflect::on(new FileMover($circuitBreaker));
});

it('does nothing if the file marked for deletion does not exist', function () {
    $diskMock = \Mockery::mock();
    $diskMock->shouldReceive('exists')->andReturnFalse();
    $diskMock->shouldReceive('size')->andReturn(0);

    Storage::shouldReceive('disk')
        ->andReturn($diskMock);

    $this->fileMover->performUndo($this->disk, $this->oldPath, $this->newPath);

    // If we get here without exception, the test passes.
    expect(true)->toBeTrue();
});

it('throws an exception when it fails to restore the file to its original location', function () {
    $diskMock = \Mockery::mock();
    $diskMock->shouldReceive('exists')
        ->once()
        ->with($this->newPath)
        ->andReturnTrue();
    $diskMock->shouldReceive('exists')
        ->twice()
        ->with($this->oldPath)
        ->andReturnFalse();
    $diskMock->shouldReceive('size')->andReturn(100);
    $diskMock->shouldReceive('copy')->andReturnFalse();

    Storage::shouldReceive('disk')
        ->andReturn($diskMock);

    expect(fn () => $this->fileMover->performUndo($this->disk, $this->oldPath, $this->newPath))
        ->toThrow(new \Exception('Failed to restore file to original location.'));
});
