<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Tests CircuitBreaker getter methods behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker
 */
it('can return the expected circuit breaker state string', function () {
    Cache::shouldReceive('get')
        ->once()
        ->andReturn('test_state');

    expect($this->breaker->getState())->toBe('test_state');
});

it('can return the expected failure count', function () {
    Cache::shouldReceive('get')
        ->once()
        ->andReturn(50);

    expect($this->breaker->getFailureCount())->toBe(50);
});

it('can return the expected array of stats', function () {
    Carbon::setTestNow(now());

    $this->breaker->shouldReceive('getState')
        ->once()
        ->andReturn('test_state');
    $this->breaker->shouldReceive('getFailureCount')
        ->once()
        ->andReturn(50);

    Cache::shouldReceive('get')
        ->once()
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
