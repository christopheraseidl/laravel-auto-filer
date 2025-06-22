<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

use christopheraseidl\ModelFiler\Jobs\Services\FileMover;
use Illuminate\Support\Facades\Log;

/**
 * Tests FileMover processUndoOperations method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('logs a warning and returns empty results when circuit breaker blocks undo operations', function () {
    $this->breaker->shouldReceive('canAttempt')->andReturnFalse();

    Log::shouldReceive('warning')
        ->with('File move undo blocked by circuit breaker', [
            'disk' => $this->disk,
            'pending_undos' => 0,
        ]);

    $this->mover->shouldReceive('getMovedFiles')
        ->twice()
        ->andReturn([]);

    $maxAttempts = 3;
    $result = $this->mover->processUndoOperations($this->disk, $maxAttempts);

    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['failures', 'successes']);
    expect($result['failures'])->toBeEmpty();
    expect($result['successes'])->toBeEmpty();
});
