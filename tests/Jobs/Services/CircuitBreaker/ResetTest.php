<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use christopheraseidl\ModelFiler\Tests\TestTraits\CircuitBreakerHelpers;
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
    $this->breaker->shouldReceive('transitionToClosed')->once();
    Log::shouldReceive('info')->once();

    $this->breaker->reset();
});

it('handles cache failures gracefully', function () {
    $this->breaker->shouldReceive('transitionToClosed')->andThrow(new \Exception('Cache failure'));

    Log::shouldReceive('warning')->once();

    expect(fn () => $this->breaker->reset())->not->toThrow(\Exception::class);
});
