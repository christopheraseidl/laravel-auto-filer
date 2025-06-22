<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileDeleter;

use christopheraseidl\ModelFiler\Jobs\Contracts\CircuitBreaker as CircuitBreakerContract;
use christopheraseidl\ModelFiler\Jobs\Services\FileDeleter;
use christopheraseidl\ModelFiler\Tests\TestTraits\FileDeleterHelpers;
use Illuminate\Support\Facades\Log;

uses(FileDeleterHelpers::class);

/**
 * Tests FileDeleter method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileDeleter
 */
beforeEach(function () {
    $this->path = 'path/to/file.txt';

    $this->breaker = $this->mock(CircuitBreakerContract::class);

    $this->deleter = $this->partialMock(FileDeleter::class);
    $this->deleter->shouldReceive('getBreaker')
        ->andReturn($this->breaker);
});

it('deletes a file and returns true', function () {
    $this->shouldValidateDeleter();

    $this->breaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->breaker->shouldReceive('recordSuccess')->once();

    $result = $this->deleter->attemptDelete($this->disk, $this->path);

    expect($result)->toBeTrue();
});

it('succeeds after 1-2 failures when maxAttempts is 3', function (int $failures) {
    $this->shouldValidateDeleter();

    $count = 0;

    $this->breaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->breaker->shouldReceive('maxAttemptsReached')->andReturnUsing(function () use ($count) {
        if ($count <= 3) {
            return false;
        }
    });

    $this->deleter->shouldReceive('performDeletion')
        ->times($failures + 1)
        ->andReturnUsing(function () use (&$count, $failures) {
            $count++;
            if ($count <= $failures) {
                throw new \Exception('Deletion failed.');
            }

            return true;
        });

    $this->breaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->breaker->shouldReceive('recordSuccess')->times($count);

    $result = $this->deleter->attemptDelete($this->disk, $this->path);

    expect($result)->toBeTrue();
})->with([
    1,
    2,
]);

it('throws an exception and logs an error after 3 errors when maxAttempts is 3', function () {
    $this->shouldValidateDeleter();

    $this->breaker->shouldReceive('canAttempt')->times(3)->andReturnTrue();

    Log::shouldReceive('warning')->times(3);

    $this->deleter->shouldReceive('handleDeletionFailure')->times(3);

    $this->breaker->shouldReceive('recordFailure')->once()->andReturnTrue();

    Log::shouldReceive('error')->once();

    expect(fn () => $this->deleter->attemptDelete($this->disk, $this->path))
        ->toThrow(\Exception::class, 'Failed to delete file after 3 attempts.');
});

it('throws exception when maxAttempts is 0', function () {
    expect(fn () => $this->deleter->attemptDelete($this->disk, $this->path, 0))
        ->toThrow(\Exception::class, 'maxAttempts must be at least 1.');
});

it('throws an exception and logs a warning when circuit breaker blocks attempt', function () {
    $this->shouldValidateDeleter();

    $this->breaker->shouldReceive('canAttempt')->andReturnFalse();

    $this->breaker->shouldReceive('recordFailure')->once()->andReturnTrue();

    Log::shouldReceive('error')->once();

    expect(fn () => $this->deleter->attemptDelete($this->disk, $this->path))
        ->toThrow(
            \Exception::class,
            'Failed to delete file after 0 attempts.'
        );
});
