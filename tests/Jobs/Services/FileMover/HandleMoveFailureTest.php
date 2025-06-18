<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\FileMover;

use christopheraseidl\HasUploads\Jobs\Services\CircuitBreaker;
use christopheraseidl\HasUploads\Jobs\Services\FileMover;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Facades\Log;

/**
 * Tests FileMover handleMoveFailure method behavior.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Services\FileMover
 */
it('logs unexpected exception when attemptUndoMove throws during move failure handling', function () {
    $circuitBreaker = \Mockery::mock(CircuitBreaker::class);
    $undoException = new \Exception('Error while moving.');

    $fileMover = \Mockery::mock(new FileMover($circuitBreaker))->makePartial();
    $fileMover = Reflect::on($fileMover);

    // Set up test state
    $fileMover->movedFiles = ['old_1' => 'new_path_1', 'old_2' => 'new_path_2'];
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
