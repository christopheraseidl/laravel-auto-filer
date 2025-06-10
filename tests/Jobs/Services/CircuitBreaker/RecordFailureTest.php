<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\CircuitBreaker;

use christopheraseidl\HasUploads\Jobs\Services\CircuitBreaker;
use christopheraseidl\HasUploads\Tests\TestTraits\CircuitBreakerHelpers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

uses(
    CircuitBreakerHelpers::class
);

beforeEach(function () {
    $this->breakerWithEmail = new CircuitBreaker(
        name: 'test-circuit-email',
        failureThreshold: 2,
        recoveryTimeout: 10,
        halfOpenMaxAttempts: 3,
        cacheTtlHours: 1,
        emailNotificationEnabled: true,
        adminEmail: 'admin@test.com'
    );

    $this->fixedTimestamp = now()->timestamp;
    $this->formattedTime = now()->format('Y-m-d H:i:s T');
});

describe('CLOSED state', function () {
    it('stays closed when failure threshold not met', function () {
        expect($this->breaker->getState())->toBe('closed');
        expect($this->breaker->getFailureCount())->toBe(0);

        $this->breaker->recordFailure();

        expect($this->breaker->getState())->toBe('closed');
        expect($this->breaker->getFailureCount())->toBe(1);
    });

    it('transitions to open when failure threshold is met', function () {
        Log::shouldReceive('warning')->once();

        $this->setFailureCount(1);
        expect($this->breaker->getFailureCount())->toBe(1);

        $this->breaker->recordFailure();

        expect($this->breaker->getState())->toBe('open');
        expect($this->breaker->getFailureCount())->toBe(2);
        expect($this->breaker->getStats()['opened_at'])->toBe($this->fixedTimestamp);
    });

    it('sends email notification when enabled and threshold met', function () {
        Log::shouldReceive('warning')->once();
        Log::shouldReceive('info')->with(
            'Circuit breaker notification sent to admin.',
            ['breaker' => 'test-circuit-email']
        )->once();

        Mail::shouldReceive('raw')->once()->with(
            \Mockery::type('string'),
            \Mockery::type('Closure')
        );

        $this->setFailureCount(1, 'test-circuit-email');

        $this->breakerWithEmail->recordFailure();

        expect($this->breakerWithEmail->getState())->toBe('open');
    });

    it('does not send email when disabled', function () {
        Log::shouldReceive('warning')->once();
        Mail::shouldReceive('raw')->never();

        $this->setFailureCount(1);

        $this->breaker->recordFailure();

        expect($this->breaker->getState())->toBe('open');
    });
});

describe('HALF_OPEN state', function () {
    beforeEach(function () {
        $this->transitionToHalfOpen();
    });

    it('stays half-open when max attempts not reached', function () {
        $this->setHalfOpenAttempts(1);

        $this->breaker->recordFailure();

        expect($this->breaker->getState())->toBe('half_open');
        expect($this->getHalfOpenAttempts())->toBe(2);
    });

    it('transitions to open when max half-open attempts reached', function () {
        Log::shouldReceive('warning')->once();

        $this->setHalfOpenAttempts(2);

        $this->breaker->recordFailure();

        expect($this->breaker->getState())->toBe('open');
        expect($this->breaker->getStats()['opened_at'])->toBe($this->fixedTimestamp);
    });

    it('sends notification when transitioning from half-open to open', function () {
        Log::shouldReceive('warning')->once();
        Log::shouldReceive('info')->with(
            'Circuit breaker notification sent to admin.',
            ['breaker' => 'test-circuit-email']
        )->once();

        Mail::shouldReceive('raw')->once();

        $this->transitionToHalfOpen('test-circuit-email');
        $this->setHalfOpenAttempts(2, 'test-circuit-email');

        $this->breakerWithEmail->recordFailure();

        expect($this->breakerWithEmail->getState())->toBe('open');
    });
});

it('handles cache failures gracefully', function () {
    config(['cache.default' => 'invalid']);

    expect(fn () => $this->breaker->recordFailure())->not->toThrow(\Exception::class);
});

it('handles email sending failures gracefully', function () {
    Log::shouldReceive('warning')->once();
    Log::shouldReceive('error')->with(
        'Failed to send circuit breaker notification.',
        \Mockery::type('array')
    )->once();

    Mail::shouldReceive('raw')->once()->andThrow(new \Exception('SMTP connection failed.'));

    $this->setFailureCount(1, 'test-circuit-email');

    expect(fn () => $this->breakerWithEmail->recordFailure())->not->toThrow(\Exception::class);
});

it('ignores failures when already in open state', function () {
    Log::shouldReceive('warning')->never();

    $this->transitionToOpen();
    $initialFailureCount = $this->breaker->getFailureCount();

    $this->breaker->recordFailure();

    expect($this->breaker->getState())->toBe('open');
});
