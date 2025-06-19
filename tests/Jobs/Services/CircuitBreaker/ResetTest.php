<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use christopheraseidl\ModelFiler\Tests\TestTraits\CircuitBreakerHelpers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

uses(
    CircuitBreakerHelpers::class
);

/**
 * Tests CircuitBreaker reset method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker
 */
it('closes the circuit breaker', function () {
    Log::shouldReceive('info')->twice();

    $this->transitionToOpen();
    Cache::put('circuit_breaker:test-circuit:half_open_attempts', 3, now()->addHours(1));

    expect($this->breaker->getState())->toBe('open');
    expect($this->breaker->getFailureCount())->toBe(2);
    expect(Cache::get('circuit_breaker:test-circuit:opened_at'))->toBe(now()->timestamp);
    expect(Cache::get('circuit_breaker:test-circuit:half_open_attempts'))->toBe(3);

    $this->breaker->reset();

    expect($this->breaker->getState())->toBe('closed');
    expect($this->breaker->getFailureCount())->toBe(0);
    expect(Cache::get('circuit_breaker:test-circuit:opened_at'))->toBeNull();
    expect(Cache::get('circuit_breaker:test-circuit:half_open_attempts'))->toBeNull();
});

it('handles cache failures gracefully', function () {
    config(['cache.default' => 'invalid']);

    expect(fn () => $this->breaker->reset())->not->toThrow(\Exception::class);
});
