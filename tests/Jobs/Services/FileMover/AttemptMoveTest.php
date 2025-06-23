<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

use christopheraseidl\ModelFiler\Tests\TestTraits\FileMoverHelpers;

uses(FileMoverHelpers::class);

/**
 * Tests FileMover attemptMove method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('moves a file and returns the new path', function () {
    $this->shouldValidateMover();

    $this->mover->shouldReceive('processMove')->andReturn($this->newPath);

    $result = $this->mover->attemptMove($this->disk, $this->oldPath, $this->newDir);

    expect($result)->toBe($this->newPath);
});

it('handles caught exceptions when move processing fails', function () {
    $this->shouldValidateMover();

    $this->mover->shouldReceive('processMove')
        ->once()
        ->andThrow(\Exception::class, 'Move processing failed');

    $this->mover->shouldReceive('handleCaughtAttemptMoveException')
        ->once();

    $result = $this->mover->attemptMove($this->disk, $this->oldPath, $this->newDir);

    expect($result)->toBeEmpty();
});

it('throws exception when maxAttempts is 0', function () {
    expect(fn () => $this->mover->attemptMove($this->disk, $this->oldPath, $this->newDir, 0))
        ->toThrow(\Exception::class, 'maxAttempts must be at least 1.');
});
