<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker;

/**
 * Tests CircuitBreaker isEmailNotificationEnabled methods behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker
 */
it('returns false when email notification is disabled', function () {
    $result = $this->breaker->isEmailNotificationEnabled(); // default

    expect($result)->toBeFalse();
});

it('returns true when email notification is enabled', function () {
    $breaker = \Mockery::mock(CircuitBreaker::class, [
        'test-circuit', // Name
        2,             // Failure threshold
        10,            // Recovery timeout in seconds
        3,             // Half-open max attempts
        1,             // Cache TTL
        true,
    ])->makePartial();

    $result = $breaker->isEmailNotificationEnabled();

    expect($result)->toBeTrue();
});
