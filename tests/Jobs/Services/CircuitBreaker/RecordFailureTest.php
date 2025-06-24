<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker;
use christopheraseidl\ModelFiler\Tests\TestTraits\CircuitBreakerHelpers;
use Illuminate\Support\Facades\Cache;

uses(
    CircuitBreakerHelpers::class
);

/**
 * Tests CircuitBreaker recordFailure method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker
 */
describe('CLOSED state', function () {
    beforeEach(function () {
        Cache::shouldReceive('get')
            ->andReturn(0);

        $this->breaker->shouldReceive('setKey')->once();
        $this->breaker->shouldReceive('isClosed')->andReturnTrue();
    });

    it('stays closed when failure threshold not met', function () {
        $this->breaker->shouldReceive('maxAttemptsReached')->once()->andReturnFalse();
        $this->breaker->shouldReceive('transitionToOpen')->never();
        $this->breaker->shouldReceive('sendAdminNotification')->never();

        $this->breaker->recordFailure();
    });

    it('transitions to open when failure threshold is met', function () {
        $this->breaker->shouldReceive('maxAttemptsReached')->once()->andReturnTrue();
        $this->breaker->shouldReceive('transitionToOpen')->once();
        $this->breaker->shouldReceive('sendAdminNotification')->once();

        $this->breaker->recordFailure();
    });
});

describe('HALF_OPEN state', function () {
    beforeEach(function () {
        Cache::shouldReceive('get')
            ->with('circuit_breaker:test-circuit:failures', 0)
            ->andReturn(1);

        $this->breaker->shouldReceive('setKey')->once();
        $this->breaker->shouldReceive('isClosed')->andReturnFalse();
        $this->breaker->shouldReceive('isHalfOpen')->andReturnTrue();
    });

    it('stays half-open when max attempts not reached', function () {
        Cache::shouldReceive('increment')->once();
        Cache::shouldReceive('get')
            ->once()
            ->with('circuit_breaker:test-circuit:half_open_attempts', 0)
            ->andReturn(2);

        $this->breaker->shouldReceive('maxAttemptsReached')->once()->andReturnFalse();
        $this->breaker->shouldReceive('transitionToOpen')->never();
        $this->breaker->shouldReceive('sendAdminNotification')->never();

        $this->breaker->recordFailure();
    });

    it('transitions to open when max half-open attempts reached', function () {
        Cache::shouldReceive('increment')->once();
        Cache::shouldReceive('get')
            ->once()
            ->with('circuit_breaker:test-circuit:half_open_attempts', 0)
            ->andReturn(2);

        $this->breaker->shouldReceive('maxAttemptsReached')->once()->andReturnTrue();
        $this->breaker->shouldReceive('transitionToOpen')->once();
        $this->breaker->shouldReceive('sendAdminNotification')->once();

        $this->breaker->recordFailure();
    });
});

it('handles cache failures gracefully', function () {
    Cache::shouldReceive('get')
        ->andThrow(\Exception::class, 'Cache failure');

    expect(fn () => $this->breaker->recordFailure())->not->toThrow(\Exception::class);
});

it('handles email sending failures gracefully', function () {
    Cache::shouldReceive('get')
        ->andReturn(0);

    $this->breaker->shouldReceive('setKey')->once();
    $this->breaker->shouldReceive('isClosed')->once()->andReturnTrue();
    $this->breaker->shouldReceive('maxAttemptsReached')->once()->andReturnTrue();
    $this->breaker->shouldReceive('transitionToOpen')->once();
    $this->breaker->shouldReceive('sendAdminNotification')->once()
        ->andThrow(\Exception::class, 'Mail failure');

    expect(fn () => $this->breaker->recordFailure())->not->toThrow(\Exception::class);
});

it('ignores failures when already in open state', function () {
    $this->breaker->shouldReceive('isClosed')->once()->andReturnFalse();
    $this->breaker->shouldReceive('isHalfOpen')->andReturnFalse();
    $this->breaker->shouldReceive('transitionToOpen')->never();
    $this->breaker->shouldReceive('sendAdminNotification')->never();

    $this->breaker->recordFailure();
});
