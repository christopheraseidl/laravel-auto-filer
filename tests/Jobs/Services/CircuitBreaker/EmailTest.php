<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\CircuitBreaker;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

it('sends email to admin with the correct content and records it in log', function () {
    $this->breaker->shouldReceive('isEmailNotificationEnabled')
        ->once()
        ->andReturnTrue();
    $this->breaker->shouldReceive('isValidEmail')->once()->andReturnTrue();
    $this->breaker->shouldReceive('getStats')
        ->andReturn([
            'attempts' => 1,
            'maxAttempts' => 2,
            'env' => 'test',
        ]);
    $this->breaker->shouldReceive('buildEmailContent')->andReturn('The breaker has opened.');
    $this->breaker->shouldReceive('getAdminEmail')
        ->andReturn('admin@mail.com');

    Mail::shouldReceive('raw')
        ->once()
        ->withArgs(function ($content, $closure) {
            if (! is_callable($closure)) {
                return false;
            }

            // Create a mock mail object to test the closure
            $mockMail = $this->mock('email');
            $mockMail->shouldReceive('to')
                ->with('admin@mail.com')
                ->andReturnSelf();
            $mockMail->shouldReceive('subject')
                ->with('Circuit breaker alert: test-circuit')
                ->andReturnSelf();

            // Execute the closure with our mock
            $closure($mockMail);

            return true;
        });

    Log::shouldReceive('info')
        ->once()
        ->with('Circuit breaker notification sent to admin.', [
            'breaker' => 'test-circuit',
        ]);

    $this->breaker->sendAdminNotification('Circuit breaker opened');
});

it('fails gracefully and logs an error when an exception is thrown', function () {
    $this->breaker->shouldReceive('isEmailNotificationEnabled')
        ->once()
        ->andReturnTrue();
    $this->breaker->shouldReceive('isValidEmail')->once()->andReturnTrue();
    $this->breaker->shouldReceive('getStats')
        ->once()
        ->andThrow(\Exception::class, 'Stats error');

    Log::shouldReceive('error')
        ->once()
        ->with('Failed to send circuit breaker notification.', [
            'breaker' => 'test-circuit',
            'exception' => 'Stats error',
        ]);

    expect(fn () => $this->breaker->sendAdminNotification('Circuit breaker opened'))
        ->not->toThrow(\Exception::class);
});

it('does nothing if email notification is disabled', function () {
    $this->breaker->shouldReceive('isEmailNotificationEnabled')
        ->once()
        ->andReturnFalse();
    $this->breaker->shouldReceive('getStats')->never();

    $this->breaker->sendAdminNotification('This will not send');
});

it('does nothing if admin email is null', function () {
    $this->breaker->shouldReceive('getProperty')
        ->with('emailNotificationEnabled')
        ->andReturn(true);
    $this->breaker->shouldReceive('getProperty')
        ->with('adminEmail')
        ->andReturn(null);
    $this->breaker->shouldReceive('getStats')->never();

    $this->breaker->sendAdminNotification('This will not send');
});
