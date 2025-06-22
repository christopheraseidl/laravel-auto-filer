<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use christopheraseidl\ModelFiler\Tests\TestTraits\CircuitBreakerHelpers;
use Illuminate\Support\Facades\Log;

uses(
    CircuitBreakerHelpers::class
);

/**
 * Tests CircuitBreaker recordSuccess method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker
 */
describe('OPEN state', function () {
    it('does not close the circuit breaker but it resets failures to 0', function () {
        $this->breaker->shouldReceive('getState')->andReturn('open');
        $this->breaker->shouldReceive('cacheForget')
            ->with('circuit_breaker:test-circuit:failures')
            ->once();

        $this->breaker->recordSuccess();

        expect($this->breaker->getState())->toBe('open');
        expect($this->breaker->getFailureCount())->toBe(0);
    });
});

describe('HALF_OPEN state', function () {
    it('closes the circuit breaker and resets failures to 0', function () {
        $this->breaker->shouldReceive('getState')->andReturn('half_open');
        $this->breaker->shouldReceive('transitionToClosed')->once();

        Log::shouldReceive('info')->once();

        $this->breaker->shouldReceive('cacheForget')
            ->with('circuit_breaker:test-circuit:failures')
            ->once();

        $this->breaker->recordSuccess();
    });
});

it('handles cache failures gracefully', function () {
    $this->breaker->shouldReceive('getState')->andThrow(new \Exception('Cache failure'));

    Log::shouldReceive('warning')->once();

    expect(fn () => $this->breaker->recordSuccess())->not->toThrow(\Exception::class);
});

it('ignores successes when already in closed state', function () {
    $this->breaker->shouldReceive('getState')->andReturn('closed');
    $this->breaker->shouldReceive('transitionToClosed')->never();

    Log::shouldReceive('info')->never();

    $this->breaker->shouldReceive('cacheForget')
        ->with('circuit_breaker:test-circuit:failures')
        ->once();

    $this->breaker->recordSuccess();
});
