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
        $this->transitionToOpen();
        $this->setFailureCount(1);

        expect($this->breaker->getState())->toBe('open');
        expect($this->breaker->getFailureCount())->toBe(1);

        $this->breaker->recordSuccess();

        expect($this->breaker->getState())->toBe('open');
        expect($this->breaker->getFailureCount())->toBe(0);
    });
});

describe('HALF_OPEN state', function () {
    beforeEach(function () {
        $this->transitionToHalfOpen();
    });

    it('closes the circuit breaker and resets failures to 0', function () {
        Log::shouldReceive('info')->twice();

        $this->transitionToHalfOpen();
        $this->setFailureCount(1);

        expect($this->breaker->getState())->toBe('half_open');
        expect($this->breaker->getFailureCount())->toBe(1);

        $this->breaker->recordSuccess();

        expect($this->breaker->getState())->toBe('closed');
        expect($this->breaker->getFailureCount())->toBe(0);
    });
});

it('handles cache failures gracefully', function () {
    config(['cache.default' => 'invalid']);

    expect(fn () => $this->breaker->recordSuccess())->not->toThrow(\Exception::class);
});

it('ignores successes when already in closed state', function () {
    Log::shouldReceive('info')->never();

    $this->transitionToClosed();

    $this->breaker->recordSuccess();

    expect($this->breaker->getState())->toBe('closed');
});
