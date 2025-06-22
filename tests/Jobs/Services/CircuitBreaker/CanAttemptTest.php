<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use christopheraseidl\ModelFiler\Tests\TestTraits\CircuitBreakerHelpers;
use Illuminate\Support\Facades\Log;

uses(
    CircuitBreakerHelpers::class
);

/**
 * Tests CircuitBreaker canAttempt method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker
 */
it('returns true when circuit breaker is in closed state', function () {
    $this->breaker->shouldReceive('getState')->andReturn('closed');

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBeTrue();
});

it('returns false when circuit is open and recovery timeout has not passed', function () {
    $this->breaker->shouldReceive('getState')->andReturn('open');
    $this->breaker->shouldReceive('cacheGet')
        ->with('circuit_breaker:test-circuit:opened_at')
        ->andReturn(now()->timestamp);

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBeFalse();
});

it('returns false when the state is invalid', function () {
    $this->breaker->shouldReceive('getState')->andReturn('test_state');

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBeFalse();
});

it('returns true when recovery timeout has passed', function () {
    $this->breaker->shouldReceive('getState')->andReturn('open');
    $this->breaker->shouldReceive('cacheGet')
        ->with('circuit_breaker:test-circuit:opened_at')
        ->andReturn(now()->subSeconds(11)->timestamp);
    $this->breaker->shouldReceive('transitionToHalfOpen')->once();

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBeTrue();
});

it('returns true when in half-open state and under max attempts', function () {
    $this->breaker->shouldReceive('getState')->andReturn('half_open');
    $this->breaker->shouldReceive('cacheGet')
        ->with('circuit_breaker:test-circuit:half_open_attempts', 0)
        ->andReturn(2);

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBeTrue();
});

it('returns false when in half-open state and at max attempts', function () {
    $this->breaker->shouldReceive('isClosed')->andReturnFalse();
    $this->breaker->shouldReceive('isOpen')->andReturnFalse();
    $this->breaker->shouldReceive('isHalfOpen')->andReturnTrue();
    $this->breaker->shouldReceive('cacheGet')
        ->once()
        ->with('circuit_breaker:test-circuit:half_open_attempts', 0)
        ->andReturn(3);

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBeFalse();
});

it('handles missing opened_at timestamp gracefully', function () {
    $this->breaker->shouldReceive('getState')->andReturn('open');
    $this->breaker->shouldReceive('cacheGet')
        ->with('circuit_breaker:test-circuit:half_open_attempts')
        ->andReturn(null);

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBeFalse();
});

it('handles cache failures gracefully by failing open', function () {
    $this->breaker->shouldReceive('getState')->andThrow(new \Exception('Cache failure'));
    Log::shouldReceive('warning')->once();

    expect($this->breaker->canAttempt())->toBeFalse();
});

it('handles time calculation edge cases correctly', function () {
    $this->breaker->shouldReceive('getState')->andReturn('open');
    $this->breaker->shouldReceive('cacheGet')
        ->with('circuit_breaker:test-circuit:opened_at')
        ->andReturn(now()->subSeconds(10)->timestamp);
    $this->breaker->shouldReceive('transitionToHalfOpen')->once();

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBeTrue();
});

it('transitions to half-open when recovery timeout has passed', function () {
    $this->breaker->shouldReceive('isClosed')->once()->andReturn(false);
    $this->breaker->shouldReceive('isOpen')->once()->andReturn(true);

    $this->breaker->shouldReceive('cacheGet')
        ->with('circuit_breaker:test-circuit:opened_at')
        ->once()
        ->andReturn(now()->subSeconds(11)->timestamp);

    $this->breaker->shouldReceive('transitionToHalfOpen')->once();

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBeTrue();
});
