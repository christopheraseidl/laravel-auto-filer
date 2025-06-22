<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker;
use christopheraseidl\ModelFiler\Tests\TestTraits\CircuitBreakerHelpers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

uses(
    CircuitBreakerHelpers::class
);

/**
 * Tests CircuitBreaker recordFailure method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker
 */
beforeEach(function () {
    $this->breakerWithEmail = \Mockery::mock(CircuitBreaker::class, [
        'test-circuit-email', // Name
        2,                    // Failure threshold
        10,                   // Recovery timeout in seconds
        3,                    // Half-open max attempts
        1,                    // Cache TTL in hours
        true,                 // Email notifications enabled
        'admin@test.com',      // Admin email
    ])->makePartial();

    $this->fixedTimestamp = now()->timestamp;
    $this->formattedTime = now()->format('Y-m-d H:i:s T');
});

describe('CLOSED state', function () {
    it('stays closed when failure threshold not met', function () {
        expect($this->breaker->getState())->toBe('closed');

        $this->breaker->shouldReceive('getState')->andReturn('closed');
        $this->breaker->shouldReceive('cacheGet')
            ->with('circuit_breaker:test-circuit:failures', 0)
            ->andReturn(0);
        $this->breaker->shouldReceive('setKey')
            ->with('failures', 1)
            ->once();

        $this->breaker->recordFailure();

        expect($this->breaker->getState())->toBe('closed');
    });

    it('transitions to open when failure threshold is met', function () {
        $this->breaker->shouldReceive('cacheGet')
            ->with('circuit_breaker:test-circuit:failures', 0)
            ->andReturn(1);

        Log::shouldReceive('warning')->once();

        $this->breaker->recordFailure();

        expect($this->breaker->getState())->toBe('open');
        expect($this->breaker->getStats()['opened_at'])->toBe($this->fixedTimestamp);
    });

    it('sends email notification when enabled and threshold met', function () {
        $this->breakerWithEmail->shouldReceive('getState')->andReturn('closed');
        $this->breakerWithEmail->shouldReceive('cacheGet')
            ->with('circuit_breaker:test-circuit-email:failures', 0)
            ->andReturn(1);
        $this->breakerWithEmail->shouldReceive('setKey')
            ->with('failures', 2)
            ->once();
        $this->breakerWithEmail->shouldReceive('transitionToOpen')->once();

        Mail::spy();

        $this->breakerWithEmail->recordFailure();

        Mail::shouldHaveReceived('raw')->once();
    });

    it('does not send email when disabled', function () {
        $this->breaker->shouldReceive('getState')->andReturn('closed');
        $this->breaker->shouldReceive('cacheGet')
            ->with('circuit_breaker:test-circuit:failures', 0)
            ->andReturn(1);
        $this->breaker->shouldReceive('setKey')
            ->with('failures', 2)
            ->once();
        $this->breaker->shouldReceive('transitionToOpen')->once();

        Mail::shouldReceive('raw')->never();

        $this->breaker->recordFailure();
    });
});

describe('HALF_OPEN state', function () {
    it('stays half-open when max attempts not reached', function () {
        $this->breaker->shouldReceive('getState')->andReturn('half_open');
        $this->breaker->shouldReceive('cacheGet')
            ->with('circuit_breaker:test-circuit:failures', 0)
            ->andReturn(0);
        $this->breaker->shouldReceive('setKey')
            ->with('failures', 1)
            ->once();
        $this->breaker->shouldReceive('cacheIncrement')
            ->with('circuit_breaker:test-circuit:half_open_attempts')
            ->once();
        $this->breaker->shouldReceive('cacheGet')
            ->with('circuit_breaker:test-circuit:half_open_attempts', 0)
            ->andReturn(1);

        $this->breaker->recordFailure();
    });

    it('transitions to open when max half-open attempts reached', function () {
        $this->breaker->shouldReceive('getState')->andReturn('half_open');
        $this->breaker->shouldReceive('cacheGet')
            ->with('circuit_breaker:test-circuit:failures', 0)
            ->andReturn(0);
        $this->breaker->shouldReceive('setKey')
            ->with('failures', 1)
            ->once();
        $this->breaker->shouldReceive('cacheIncrement')
            ->with('circuit_breaker:test-circuit:half_open_attempts')
            ->once();
        $this->breaker->shouldReceive('cacheGet')
            ->with('circuit_breaker:test-circuit:half_open_attempts', 0)
            ->andReturn(3);
        $this->breaker->shouldReceive('transitionToOpen')->once();
        $this->breaker->shouldReceive('sendAdminNotification')
            ->with('Circuit breaker reopened after 3 half-open attempts.')
            ->once();

        $this->breaker->recordFailure();
    });

    it('sends notification when transitioning from half-open to open', function () {
        $this->breakerWithEmail->shouldReceive('getState')->andReturn('half_open');
        $this->breakerWithEmail->shouldReceive('cacheGet')
            ->with('circuit_breaker:test-circuit-email:failures', 0)
            ->andReturn(0);
        $this->breakerWithEmail->shouldReceive('setKey')
            ->with('failures', 1)
            ->once();
        $this->breakerWithEmail->shouldReceive('cacheIncrement')
            ->with('circuit_breaker:test-circuit-email:half_open_attempts')
            ->once();
        $this->breakerWithEmail->shouldReceive('cacheGet')
            ->with('circuit_breaker:test-circuit-email:half_open_attempts', 0)
            ->andReturn(3);
        $this->breakerWithEmail->shouldReceive('transitionToOpen')->once();

        $this->breakerWithEmail->shouldReceive('sendAdminNotification')->once();

        $this->breakerWithEmail->recordFailure();
    });
});

it('handles cache failures gracefully', function () {
    config(['cache.default' => 'invalid']);

    expect(fn () => $this->breaker->recordFailure())->not->toThrow(\Exception::class);
});

it('handles email sending failures gracefully', function () {
    $this->breakerWithEmail->shouldReceive('getState')->andReturn('closed');
    $this->breakerWithEmail->shouldReceive('cacheGet')
        ->with('circuit_breaker:test-circuit-email:failures', 0)
        ->andReturn(1);
    $this->breakerWithEmail->shouldReceive('setKey')
        ->with('failures', 2)
        ->once();
    $this->breakerWithEmail->shouldReceive('transitionToOpen')->once();

    $this->breakerWithEmail->shouldReceive('sendAdminNotification')
        ->once()
        ->andThrow(new \Exception('SMTP connection failed.'));

    Log::shouldReceive('warning')->once();

    expect(fn () => $this->breakerWithEmail->recordFailure())->not->toThrow(\Exception::class);
});

it('ignores failures when already in open state', function () {
    $this->breaker->shouldReceive('getState')->andReturn('open');
    $this->breaker->shouldReceive('cacheGet')
        ->with('circuit_breaker:test-circuit:failures', 0)
        ->andReturn(2);
    $this->breaker->shouldReceive('setKey')
        ->with('failures', 3)
        ->once();
    $this->breaker->shouldReceive('transitionToOpen')->never();

    $this->breaker->recordFailure();
});
