<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileDeleter;

use Illuminate\Support\Facades\Log;

it('records a circuit breaker success and returns true on success', function () {
    $this->breaker->shouldReceive('recordSuccess')->once();

    $result = $this->deleter->handleDeletionResult(true);

    expect($result)->toBeTrue();
});

it('records a circuit breaker failure and throws an exception on failure', function () {
    $this->breaker->shouldReceive('recordFailure')->once();

    expect(fn () => $this->deleter->handleDeletionResult(false))
        ->toThrow(\Exception::class, 'Deletion returned false');
});

it('handles a caught exception, logs a warning, and waits when allowed', function () {
    $path = 'path/to/dir';
    $attempts = 2;
    $maxAttempts = 3;
    $exceptionMessage = 'Exception caught by handler';

    Log::shouldReceive('warning')
        ->once()
        ->with('File delete attempt 2 failed.', [
            'disk' => $this->disk,
            'path' => $path,
            'exception' => $exceptionMessage,
            'attempt' => $attempts,
            'max_attempts' => $maxAttempts,
        ]);

    $this->breaker->shouldReceive('maxAttemptsReached')->once()->andReturnFalse();
    $this->breaker->shouldReceive('canAttempt')->once()->andReturnTrue();
    $this->deleter->shouldReceive('waitBeforeRetry')->once();

    $this->deleter->handleProcessDeletionException(
        $this->disk,
        $path,
        $attempts,
        $maxAttempts,
        $exceptionMessage);
});

it('handles a caught exception, logs a warning, and does not wait when when max attempts reached', function () {
    $path = 'path/to/dir';
    $attempts = 2;
    $maxAttempts = 3;
    $exceptionMessage = 'Exception caught by handler';

    Log::shouldReceive('warning')
        ->once()
        ->with('File delete attempt 2 failed.', [
            'disk' => $this->disk,
            'path' => $path,
            'exception' => $exceptionMessage,
            'attempt' => $attempts,
            'max_attempts' => $maxAttempts,
        ]);

    $this->breaker->shouldReceive('maxAttemptsReached')->once()->andReturnTrue();
    $this->deleter->shouldReceive('waitBeforeRetry')->never();

    $this->deleter->handleProcessDeletionException(
        $this->disk,
        $path,
        $attempts,
        $maxAttempts,
        $exceptionMessage);
});

it('handles a caught exception, logs a warning, and does not wait when when blocked by breaker', function () {
    $path = 'path/to/dir';
    $attempts = 2;
    $maxAttempts = 3;
    $exceptionMessage = 'Exception caught by handler';

    Log::shouldReceive('warning')
        ->once()
        ->with('File delete attempt 2 failed.', [
            'disk' => $this->disk,
            'path' => $path,
            'exception' => $exceptionMessage,
            'attempt' => $attempts,
            'max_attempts' => $maxAttempts,
        ]);

    $this->breaker->shouldReceive('maxAttemptsReached')->once()->andReturnFalse();
    $this->breaker->shouldReceive('canAttempt')->once()->andReturnFalse();
    $this->deleter->shouldReceive('waitBeforeRetry')->never();

    $this->deleter->handleProcessDeletionException(
        $this->disk,
        $path,
        $attempts,
        $maxAttempts,
        $exceptionMessage);
});

it('logs an error and throws an exception on deletion failure', function () {
    $this->breaker->shouldReceive('recordFailure')->once();

    $path = 'path/to/dir';
    $attempts = 2;
    $exceptionMessage = 'Exception caught by handler';

    Log::shouldReceive('error')
        ->once()
        ->with('File deletion failed after 2 attempts.', [
            'disk' => $this->disk,
            'path' => $path,
            'max_attempts' => $attempts,
            'last_exception' => $exceptionMessage,
        ]);

    expect(fn () => $this->deleter->handleDeletionFailure($this->disk, $path, $attempts, $exceptionMessage))
        ->toThrow(\Exception::class, 'Failed to delete file after 2 attempts.');
});
