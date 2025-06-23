<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

/**
 * Tests FileMover processSingleUndo() method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('succeeds after 1-2 failures when maxAttempts is 3', function (int $failures) {
    $count = 0;

    $this->breaker->shouldReceive('canAttempt')->times($failures + 1)->andReturnTrue();

    $this->mover->shouldReceive('performUndo')
        ->times($failures + 1)
        ->with($this->disk, $this->oldPath, $this->newPath)
        ->andReturnUsing(function () use (&$count, $failures) {
            $count++;
            if ($count <= $failures) {
                throw new \Exception('Undo move failed');
            }

        });

    $this->mover->shouldReceive('handleCaughtProcessSingleUndoException')->times($failures);

    $this->breaker->shouldReceive('recordSuccess')->once();

    $maxAttempts = 3;
    $result = $this->mover->processSingleUndo($this->disk, $this->oldPath, $this->newPath, $maxAttempts);

    expect($result)->toBeTrue();
})->with([
    1,
    2,
]);

it('fails to roll back and records a circuit breaker failure when maximum attempts reached', function () {
    $this->breaker->shouldReceive('canAttempt')
        ->once()
        ->andReturnFalse();

    $attempts = 0;
    $maxAttempts = 3;

    $this->breaker->shouldReceive('maxAttemptsReached')
        ->once()
        ->with($attempts, $maxAttempts)
        ->andReturnTrue();

    $this->breaker->shouldReceive('recordFailure')->once();

    $result = $this->mover->processSingleUndo($this->disk, $this->oldPath, $this->newPath, $maxAttempts);

    expect($result)->toBeFalse();
});
