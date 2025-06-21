<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use Illuminate\Support\Carbon;

/**
 * Tests CircuitBreaker getter methods behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker
 */
test('getState returns the expected string', function () {
    $this->breaker->shouldReceive('cacheGet')
        ->with('circuit_breaker:test-circuit:state', 'closed')
        ->andReturn('test_state');

    expect($this->breaker->getState())->toBe('test_state');
});

test('getFailureCount returns the expected integer', function () {
    $this->breaker->shouldReceive('cacheGet')
        ->with('circuit_breaker:test-circuit:failures', 0)
        ->andReturn(50);

    expect($this->breaker->getFailureCount())->toBe(50);
});

test('getStats returns the expected array', function () {
    Carbon::setTestNow(now());

    $this->breaker->shouldReceive('getState')
        ->andReturn('test_state');
    $this->breaker->shouldReceive('getFailureCount')
        ->andReturn(50);
    $this->breaker->shouldReceive('cacheGet')
        ->with('circuit_breaker:test-circuit:opened_at')
        ->andReturn(now()->subHours(1));

    $stats = [
        'name' => 'test-circuit',
        'state' => 'test_state',
        'failure_count' => 50,
        'failure_threshold' => 2,
        'opened_at' => now()->subHours(1),
        'recovery_timeout' => 10,
    ];

    expect($this->breaker->getStats())->toEqual($stats);
});
