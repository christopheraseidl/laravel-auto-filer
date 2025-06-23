<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

/**
 * Tests FileMover performMove method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('can validate a successful storage result', function () {
    $this->mover->shouldReceive('getBreaker')
        ->never();

    $this->mover->validateStorageResult(true, 'Test failure message');

    // Test passes if no exception is thrown
    expect(true)->toBeTrue();
});

it('can record failure and throw exception when storage operation fails', function () {
    $this->breaker->shouldReceive('recordFailure')
        ->once();

    $message = 'Test failure message';

    expect(fn () => $this->mover->validateStorageResult(false, $message))
        ->toThrow(\Exception::class, $message);
});

it('does nothing when copied file exists at destination', function () {
    $this->mover->shouldReceive('fileExists')
        ->once()
        ->with($this->disk, $this->newPath)
        ->andReturnTrue();

    $this->mover->shouldReceive('getBreaker')
        ->never();

    $this->mover->validateCopiedFile($this->disk, $this->newPath);

    // Test passes if no exception is thrown
    expect(true)->toBeTrue();
});

it('records failure and throws exception when copied file does not exist', function () {
    $this->mover->shouldReceive('fileExists')
        ->once()
        ->with($this->disk, $this->newPath)
        ->andReturnFalse();

    $this->breaker->shouldReceive('recordFailure')
        ->once();

    expect(fn () => $this->mover->validateCopiedFile($this->disk, $this->newPath))
        ->toThrow(\Exception::class, 'Copy succeeded but file not found at destination.');
});
