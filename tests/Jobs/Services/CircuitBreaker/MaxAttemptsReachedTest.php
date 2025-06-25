<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use Illuminate\Support\Facades\Cache;

/**
 * Tests CircuitBreaker maxHalfOpenAttemptsReached method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker
 */
it('correctly determines whether half-open max attempts has been reached', function (int $attempts) {
    $max = 3; // configured in Pest.php

    Cache::shouldReceive('get')->once()->andReturn($attempts);

    $expected = $attempts < $max;

    $this->breaker->shouldReceive('maxAttemptsReached')
        ->with($attempts, $max)
        ->andReturn($expected);

    $result = $this->breaker->maxHalfOpenAttemptsReached($attempts, $max);

    expect($result)->toBe($expected);
})->with([1, 2, 3]);

it('correctly determines whether max attempts has been reached', function (int $attempts) {
    $max = 2; // configured in Pest.php
    $expected = $attempts >= $max;

    $result = $this->breaker->maxAttemptsReached($attempts, $max);

    expect($result)->toBe($expected);
})->with([1, 2, 3]);
