<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use christopheraseidl\ModelFiler\Tests\TestTraits\CircuitBreakerHelpers;

uses(
    CircuitBreakerHelpers::class
);

/**
 * Tests CircuitBreaker reset method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker
 */
it('closes the circuit breaker', function () {
    $this->breaker->shouldReceive('transitionToClosed')->once();
    $this->breaker->shouldReceive('logInfo')->once();

    $this->breaker->reset();
});

it('handles cache failures gracefully', function () {
    $this->breaker->shouldReceive('transitionToClosed')->andThrow(new \Exception('Cache failure'));
    $this->breaker->shouldReceive('logWarning')->once();

    expect(fn () => $this->breaker->reset())->not->toThrow(\Exception::class);
});
