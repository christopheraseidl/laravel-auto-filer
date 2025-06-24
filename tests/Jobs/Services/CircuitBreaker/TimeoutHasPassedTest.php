<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use Illuminate\Support\Facades\Cache;

it('handles time calculation edge cases correctly', function () {
    $this->breaker->shouldReceive('isClosed')->andReturn('open');

    Cache::shouldReceive('get')
        ->once()
        ->andReturn(now()->subSeconds(10)->timestamp);

    $this->breaker->shouldReceive('transitionToHalfOpen')->once();

    $timeoutPassed = $this->breaker->timeoutHasPassed();

    expect($timeoutPassed)->toBeTrue();
});
