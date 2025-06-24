<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

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
    $this->breaker->shouldReceive('isClosed')->once()->andReturnFalse();
    $this->breaker->shouldReceive('isOpen')->once()->andReturnTrue();
    $this->breaker->shouldReceive('timeoutHasPassed')->once()->andReturnTrue();
    $this->breaker->shouldReceive('transitionToHalfOpen')->once();

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBeTrue();
});

it('returns true when in half-open state and under max attempts', function () {
    $this->breaker->shouldReceive('isClosed')->once()->andReturnFalse();
    $this->breaker->shouldReceive('isOpen')->once()->andReturnFalse();
    $this->breaker->shouldReceive('isHalfOpen')->once()->andReturnTrue();

    $this->breaker->shouldReceive('maxHalfOpenAttemptsReached')->once()->andReturnFalse();

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBeTrue();
});

it('returns false when in half-open state and at max attempts', function () {
    $this->breaker->shouldReceive('isClosed')->once()->andReturnFalse();
    $this->breaker->shouldReceive('isOpen')->once()->andReturnFalse();
    $this->breaker->shouldReceive('isHalfOpen')->once()->andReturnTrue();

    $this->breaker->shouldReceive('maxHalfOpenAttemptsReached')->once()->andReturnTrue();

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
    $this->breaker->shouldReceive('isClosed')->andThrow(new \Exception('Cache failure'));

    expect($this->breaker->canAttempt())->toBeFalse();
});

it('transitions to half-open when recovery timeout has passed', function () {
    $this->breaker->shouldReceive('isClosed')->once()->andReturnFalse();
    $this->breaker->shouldReceive('isOpen')->once()->andReturnTrue();
    $this->breaker->shouldReceive('timeoutHasPassed')->andReturnTrue();

    $this->breaker->shouldReceive('transitionToHalfOpen')->once();

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBeTrue();
});
