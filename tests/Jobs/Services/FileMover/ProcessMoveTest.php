<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

use Illuminate\Support\Facades\Log;

/**
 * Tests FileMover processMove method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
beforeEach(function () {
    $name = 'test.txt';
    $this->oldPath = "uploads/{$name}";
    $this->newDir = 'new/dir';
    $this->newPath = "{$this->newDir}/{$name}";
});

it('logs warnings, calls handleMoveFailure, and succeeds after 1-2 failures when maxAttempts is 3', function (int $failures) {
    $count = 0;

    $this->breaker->shouldReceive('canAttempt')->andReturnTrue();

    $this->mover->shouldReceive('performMove')
        ->times($failures + 1)
        ->andReturnUsing(function () use (&$count, $failures) {
            $count++;
            if ($count <= $failures) {
                throw new \Exception('Move failed.');
            }

            return $this->newPath;
        });

    Log::shouldReceive('warning')->times($failures);

    $this->mover->shouldReceive('handleMoveFailure')->times($failures);

    $this->breaker->shouldReceive('maxAttemptsReached')->andReturnUsing(function () use ($count) {
        if ($count <= 3) {
            return false;
        }
    });

    $maxAttempts = 3;

    $result = $this->mover->processMove($this->disk, $this->oldPath, $this->newPath, $maxAttempts);

    expect($result)->toBe($this->newPath);
})->with([
    1,
    2,
]);

it('logs an error, records a circuit breaker failure, and throws last exception when max attempts reached', function () {
    $maxAttempts = 3;

    $this->breaker->shouldReceive('canAttempt')->andReturnTrue();

    $this->mover->shouldReceive('performMove')
        ->times($maxAttempts)
        ->andThrow(\Exception::class, 'Move failed');

    Log::shouldReceive('warning')->times($maxAttempts);

    $this->mover->shouldReceive('handleMoveFailure')->times($maxAttempts);

    $this->breaker->shouldReceive('maxAttemptsReached')->once()->andReturnTrue();
    $this->breaker->shouldReceive('recordFailure')->once();

    expect(fn () => $this->mover->processMove($this->disk, $this->oldPath, $this->newPath, $maxAttempts))
        ->toThrow(\Exception::class, 'Move failed');
});
