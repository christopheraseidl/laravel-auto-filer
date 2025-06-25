<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

it('transitions to open and logs a warning', function () {
    $this->breaker->shouldReceive('setKey')
        ->once()
        ->with('state', 'open');

    $this->breaker->shouldReceive('setKey')
        ->once()
        ->with('opened_at', now()->timestamp);

    Cache::shouldReceive('forget')
        ->once()
        ->with('circuit_breaker:test-circuit:half_open_attempts');

    Log::shouldReceive('warning')->once();

    $this->breaker->shouldReceive('getTimestamp')->once();
    $this->breaker->shouldReceive('getStats')->once();

    $this->breaker->transitionToOpen();
});

it('transitions to half-open and logs a warning', function () {
    $this->breaker->shouldReceive('setKey')
        ->once()
        ->with('state', 'half_open');

    $this->breaker->shouldReceive('setKey')
        ->once()
        ->with('half_open_attempts', 0);

    Log::shouldReceive('warning')->once();

    $this->breaker->transitionToHalfOpen();
});

it('transitions to closed and logs a warning', function () {
    Cache::shouldReceive('forget')
        ->once()
        ->with('circuit_breaker:test-circuit:state');

    Cache::shouldReceive('forget')
        ->once()
        ->with('circuit_breaker:test-circuit:failures');

    Cache::shouldReceive('forget')
        ->once()
        ->with('circuit_breaker:test-circuit:opened_at');

    Cache::shouldReceive('forget')
        ->once()
        ->with('circuit_breaker:test-circuit:half_open_attempts');

    Log::shouldReceive('info')->once();

    $this->breaker->shouldReceive('getTimestamp')->once();
    $this->breaker->shouldReceive('getStats')->once();

    $this->breaker->transitionToClosed();
});
