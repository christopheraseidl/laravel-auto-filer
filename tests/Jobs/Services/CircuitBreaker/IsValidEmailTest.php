<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

/**
 * Tests CircuitBreaker isEmailNotificationEnabled methods behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker
 */
it('returns true with a valid email', function () {
    $this->breaker->shouldReceive('getAdminEmail')
        ->twice()
        ->andReturn('mail@example.com');

    $result = $this->breaker->isValidEmail();

    expect($result)->toBeTrue();
});

it('returns false when the email is null or empty', function (mixed $value) {
    $this->breaker->shouldReceive('getAdminEmail')
        ->once()
        ->andReturn($value);

    $result = $this->breaker->isValidEmail();

    expect($result)->toBeFalse();
})->with([
    null,
    '',
]);

it('returns false when the email is invalid', function (mixed $value) {
    $this->breaker->shouldReceive('getAdminEmail')
        ->twice()
        ->andReturn($value);

    $result = $this->breaker->isValidEmail();

    expect($result)->toBeFalse();
})->with([
    1,
    'mail@mail',
    'random_string',
]);
