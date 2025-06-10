<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\CircuitBreaker;

use christopheraseidl\HasUploads\Tests\TestTraits\CircuitBreakerHelpers;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

uses(
    CircuitBreakerHelpers::class
);

it('returns true when circuit breaker is in closed state', function () {
    expect($this->breaker->getState())->toBe('closed');

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBe(true);
});

it('returns false when circuit is open and recovery timeout has not passed', function () {
    $this->transitionToOpen();
    expect($this->breaker->getState())->toBe('open');

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBe(false);
});

it('transitions to half-open and returns true when recovery timeout has passed', function () {
    Log::shouldReceive('warning')->once(); // For the half-open transition log

    $this->transitionToOpen();
    Carbon::setTestNow(now()->addSeconds(11));

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBe(true);
    expect($this->breaker->getState())->toBe('half_open');
    expect($this->getHalfOpenAttempts())->toBe(0);
});

it('returns true when in half-open state and under max attempts', function () {
    $this->transitionToHalfOpen();
    $this->setHalfOpenAttempts(2); // Under the limit of 3

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBe(true);
});

it('returns false when in half-open state and at max attempts', function () {
    $this->transitionToHalfOpen();
    $this->setHalfOpenAttempts(3); // At the limit of 3

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBe(false);
});

it('handles missing opened_at timestamp gracefully', function () {
    $this->transitionToOpen();

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBe(false);
});

it('handles cache failures gracefully by failing open', function () {
    config(['cache.default' => 'invalid']);

    expect($this->breaker->canAttempt())
        ->toBeTrue();
});

it('handles time calculation edge cases correctly', function () {
    $this->transitionToOpen();
    Carbon::setTestNow(now()->addSeconds(10)); // Exactly at timeout

    $canAttempt = $this->breaker->canAttempt();

    expect($canAttempt)->toBe(true);
    expect($this->breaker->getState())->toBe('half_open');
});

it('does not transition states multiple times on repeated calls', function () {
    Log::shouldReceive('warning')->once();

    $this->transitionToOpen();
    Carbon::setTestNow(now()->addSeconds(11));

    $result1 = $this->breaker->canAttempt();
    $result2 = $this->breaker->canAttempt();
    $result3 = $this->breaker->canAttempt();

    expect($result1)->toBe(true);
    expect($result2)->toBe(true);
    expect($result3)->toBe(true);
    expect($this->breaker->getState())->toBe('half_open');
});
