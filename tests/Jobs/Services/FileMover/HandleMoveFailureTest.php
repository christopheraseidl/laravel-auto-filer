<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\FileMover;

use christopheraseidl\HasUploads\Jobs\Services\CircuitBreaker;
use christopheraseidl\HasUploads\Jobs\Services\FileMover;
use Illuminate\Support\Facades\Log;

/**
 * Tests FileMover handleMoveFailure method behavior.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Services\FileMover
 */
it('logs unexpected exception when attemptUndoMove throws during move failure handling', function () {
    $circuitBreaker = \Mockery::mock(CircuitBreaker::class);
    $circuitBreaker->shouldReceive('maxAttemptsReached')->andReturnTrue();
    $circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();

    $fileMover = \Mockery::mock(FileMover::class, [$circuitBreaker, [
        'old_1' => 'new_path_1',
        'old_2' => 'new_path_2',
    ]])->makePartial();

    $undoException = new \Exception('Error while moving.');

    // Set up test state
    $fileMover->shouldReceive('attemptUndoMove')
        ->andThrow($undoException);

    Log::spy();

    $attempt = 1;
    $maxAttempts = 1;
    $fileMover->handleMoveFailure($this->disk, $attempt, $maxAttempts);

    Log::shouldHaveReceived('error')
        ->once()
        ->with('Unexpected exception during undo after move failure.', [
            'disk' => $this->disk,
            'error' => $undoException->getMessage(),
        ]);
});
