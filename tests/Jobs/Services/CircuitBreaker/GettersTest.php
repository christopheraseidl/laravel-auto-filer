<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\CircuitBreaker;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

test('getState returns the expected string', function () {
    Cache::shouldReceive('get')
        ->andReturn('test_state');

    expect($this->breaker->getState())->toBe('test_state');
});

test('getFailureCount returns the expected integer', function () {
    Cache::shouldReceive('get')
        ->andReturn(50);

    expect($this->breaker->getFailureCount())->toBe(50);
});

test('getStats returns the expected array', function () {
    Carbon::setTestNow(now());

    Cache::shouldReceive('get')
        ->with('circuit_breaker:test-circuit:state', 'closed')
        ->andReturn('test_state');

    Cache::shouldReceive('get')
        ->with('circuit_breaker:test-circuit:failures', 0)
        ->andReturn(50);

    Cache::shouldReceive('get')
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
