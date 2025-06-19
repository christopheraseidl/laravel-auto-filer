<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Tests CircuitBreaker is... boolean methods behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker
 */
it('returns expected value', function (string $name) {
    $method = Str::pascal($name);
    $method = "is{$method}";

    Cache::shouldReceive('get')
        ->with('circuit_breaker:test-circuit:state', 'closed')
        ->andReturn($name);

    $bool = $this->breaker->$method();

    expect($bool)->toBe(true);
})->with([
    'closed',
    'open',
    'half_open',
]);
