<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\FileMover;

use christopheraseidl\HasUploads\Jobs\Services\CircuitBreaker;
use christopheraseidl\HasUploads\Jobs\Services\FileMover;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Facades\Storage;

/**
 * Tests FileMover performMove method behavior.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Services\FileMover
 */
beforeEach(function () {
    $name = 'test.txt';
    $this->oldPath = "uploads/{$name}";
    $this->newDir = 'new/dir';
    $this->newPath = "{$this->newDir}/{$name}";
});

it('records a circuit breaker failure and throws an exception when copied file not found at destination', function () {
    $breakerMock = \Mockery::mock(CircuitBreaker::class);
    $breakerMock->shouldReceive('recordFailure')->once();

    $mover = new FileMover($breakerMock);
    $moverReflection = Reflect::on($mover);

    // Mock the storage operations
    $diskMock = \Mockery::mock();
    $diskMock->shouldReceive('copy')
        ->andReturn(true);
    $diskMock->shouldReceive('exists')
        ->with($this->newPath)
        ->andReturnFalse();
    $diskMock->shouldReceive('size')
        ->with($this->newPath)
        ->andReturn(0);

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->andReturn($diskMock);

    expect(fn () => $moverReflection->performMove($this->disk, $this->oldPath, $this->newPath))
        ->toThrow(new \Exception('Copy succeeded but file not found at destination.'));
});
