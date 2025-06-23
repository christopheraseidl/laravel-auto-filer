<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

/**
 * Tests FileMover attemptUndoMove method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('handles successful undo move operations and returns results', function () {
    $maxAttempts = 3;

    $this->mover->shouldReceive('validateMaxAttempts')
        ->once()
        ->with($maxAttempts);

    $expectation = [
        'failures' => [],
        'successes' => ['path/to/file.txt', 'path/to/image.png', 'path/to/record.log'],
    ];

    $this->mover->shouldReceive('processAllUndoOperations')
        ->once()
        ->with($this->disk, $maxAttempts)
        ->andReturn($expectation);

    $this->mover->shouldReceive('handleAttemptUndoSuccess')
        ->once();

    $result = $this->mover->attemptUndoMove($this->disk);

    expect($result)->toBe($expectation);
});

it('handles failed undo move operations and returns results', function () {
    $maxAttempts = 3;

    $this->mover->shouldReceive('validateMaxAttempts')
        ->once()
        ->with($maxAttempts);

    $expectation = [
        'failures' => ['path/to/failure.txt'],
        'successes' => ['path/to/image.png', 'path/to/record.log'],
    ];

    $this->mover->shouldReceive('processAllUndoOperations')
        ->once()
        ->with($this->disk, $maxAttempts)
        ->andReturn($expectation);

    $this->mover->shouldReceive('handleAttemptUndoFailure')
        ->once();

    $result = $this->mover->attemptUndoMove($this->disk);

    expect($result)->toBe($expectation);
});

it('throws an invalid argument exception when maxAttempts is less than 1', function () {
    $this->mover->attemptUndoMove($this->disk, 0);
})->throws(\InvalidArgumentException::class, 'maxAttempts must be at least 1.');
