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

it('builds the expected email content', function () {
    $message = 'test message';
    $stats = [
        'name' => 'Test circuit breaker',
        'state' => 'open',
        'failure_count' => 1,
        'failure_threshold' => 3,
        'recovery_timeout' => 60,
    ];
    $timestamp = now();

    $expected = "
    Circuit breaker alert: Laravel
    Time: {$timestamp}

    {$message}

    Details:
    - Name: {$stats['name']} 
    - Current state: {$stats['state']}
    - Failure count: {$stats['failure_count']} / {$stats['failure_threshold']}
    - Recovery timeout: {$stats['recovery_timeout']} seconds

    This is an automatic notification. Please check the application logs for more details.
    ";

    $result = $this->breaker->buildEmailContent($message, $stats);

    expect($result)->toContain('Circuit breaker alert: Laravel')
        ->and($result)->toContain('Name: Test circuit breaker')
        ->and($result)->toContain("Time: {$timestamp}")
        ->and($result)->toContain('Failure count: 1 / 3')
        ->and($result)->toContain('Recovery timeout: 60 seconds');
});
