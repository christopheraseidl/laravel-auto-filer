<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

/**
 * Tests FileMover processMove method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('succeeds after 1-2 failures when maxAttempts is 3', function (int $failures) {
    $count = 0;

    $this->breaker->shouldReceive('canAttempt')->andReturnTrue();

    $this->mover->shouldReceive('performMove')
        ->times($failures + 1)
        ->andReturnUsing(function () use (&$count, $failures) {
            $count++;
            if ($count <= $failures) {
                throw new \Exception('Move failed');
            }

            return $this->newPath;
        });

    $this->mover->shouldReceive('handleProcessMoveCaughtException')->times($failures);

    $maxAttempts = 3;

    $result = $this->mover->processMove($this->disk, $this->oldPath, $this->newPath, $maxAttempts);

    expect($result)->toBe($this->newPath);
})->with([
    1,
    2,
]);

it('handles a move failure when no exception is thrown', function () {
    $maxAttempts = 3;

    $this->breaker->shouldReceive('canAttempt')->andReturnFalse();

    $this->mover->shouldReceive('performMove')->never();

    $this->mover->shouldReceive('handleProcessMoveFailure')
        ->once()
        ->with(0, 3, null);

    $result = $this->mover->processMove($this->disk, $this->oldPath, $this->newPath, $maxAttempts);

    expect($result)->toBeEmpty();
});
