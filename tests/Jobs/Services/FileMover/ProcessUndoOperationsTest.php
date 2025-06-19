<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

use christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker;
use christopheraseidl\ModelFiler\Jobs\Services\FileMover;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;

/**
 * Tests FileMover processUndoOperations method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('logs a warning and returns empty results when circuit breaker blocks undo operations', function () {
    $breakerStats = [
        'test_stat_1' => 'value',
        'test_stat_2' => 'valued',
    ];

    $circuitBreaker = $this->mock(CircuitBreaker::class, function (MockInterface $mock) use ($breakerStats) {
        $mock->shouldReceive('canAttempt')->andReturn(false);
        $mock->shouldReceive('getStats')->andReturn($breakerStats);
    });

    $fileMover = new FileMover($circuitBreaker);
    $moverReflection = Reflect::on($fileMover);

    Log::spy();

    $maxAttempts = 3;
    $result = $moverReflection->processUndoOperations($this->disk, $maxAttempts);

    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['failures', 'successes']);
    expect($result['failures'])->toBeEmpty();
    expect($result['successes'])->toBeEmpty();

    Log::shouldHaveReceived('warning')
        ->with('File operation blocked by circuit breaker.', [
            'operation' => 'process undo operations',
            'disk' => $this->disk,
            'breaker_stats' => $breakerStats,
            'pending_undos' => count($moverReflection->movedFiles),
        ]);
});
