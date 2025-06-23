<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

/**
 * Tests FileMover processUndoOperations method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('processes all undo operations and returns a results array', function () {
    $this->breaker->shouldReceive('canAttempt')
        ->once()
        ->andReturnTrue();

    $this->mover->shouldReceive('getMovedFiles')
        ->once()
        ->andReturn([
            'old/path/to/image.png' => 'new/path/to/image.png',
            'old/path/to/document.txt' => 'new/path/to/document.txt',
        ]);

    // One success
    $this->mover->shouldReceive('processSingleUndo')
        ->once()
        ->andReturnTrue();

    // One failure
    $this->mover->shouldReceive('processSingleUndo')
        ->once()
        ->andReturnFalse();

    $maxAttempts = 3;
    $result = $this->mover->processAllUndoOperations($this->disk, $maxAttempts);

    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['failures', 'successes']);
    expect($result['failures'])->toHaveCount(1);
    expect($result['successes'])->toHaveCount(1);
});

it('handles circuit breaker blocks and returns a results array', function () {
    $this->breaker->shouldReceive('canAttempt')
        ->once()
        ->andReturnFalse();

    $this->mover->shouldReceive('handleCircuitBreakerBlock')
        ->once();

    $this->mover->shouldReceive('getMovedFiles')
        ->once()
        ->andReturn([
            'old/path/to/image.png' => 'new/path/to/image.png',
            'old/path/to/document.txt' => 'new/path/to/document.txt',
        ]);

    $maxAttempts = 3;
    $result = $this->mover->processAllUndoOperations($this->disk, $maxAttempts);

    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['failures', 'successes']);
    expect($result['failures'])->toHaveCount(2);
    expect($result['successes'])->toBeEmpty();
});
