<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileDeleter;

use christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker;
use christopheraseidl\ModelFiler\Jobs\Services\FileDeleter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

/**
 * Tests FileDeleter method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileDeleter
 */
beforeEach(function () {
    $this->deleter = new FileDeleter(
        new CircuitBreaker('test-breaker')
    );
    $this->path = 'uploads/file.txt';

    Storage::disk($this->disk)->put($this->path, 'test file content');
});

it('deletes a file and returns true', function () {
    $result = $this->deleter->attemptDelete($this->disk, $this->path);

    expect($result)->toBeTrue();
    expect(Storage::disk($this->disk)->exists($this->path))->toBeFalse();
});

it('succeeds after 1-2 failures when maxAttempts is 3', function (int $failures) {
    $count = 0;
    $diskMock = \Mockery::mock();
    $diskMock->shouldReceive('directoryExists')->andReturnFalse();
    $diskMock->shouldReceive('delete')
        ->times($failures + 1)
        ->andReturnUsing(function () use (&$count, $failures) {
            $count++;
            if ($count <= $failures) {
                throw new \Exception('Deletion failed.');
            }

            return true;
        });

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->andReturn($diskMock);

    $result = $this->deleter->attemptDelete($this->disk, $this->path);

    expect($result)->toBeTrue();
})->with([
    1,
    2,
]);

it('throws an exception and logs an error after 3 errors when maxAttempts is 3', function () {
    $diskMock = \Mockery::mock();
    $diskMock->shouldReceive('delete')
        ->andThrow(new \Exception('Deletion failed.'));

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->andReturn($diskMock);

    Log::spy();

    expect(fn () => $this->deleter->attemptDelete($this->disk, $this->path))
        ->toThrow(\Exception::class);

    Log::shouldHaveReceived('error');
});

it('throws exception when maxAttempts is 0', function () {
    expect(fn () => $this->deleter->attemptDelete($this->disk, $this->path, 0))
        ->toThrow(\Exception::class, 'maxAttempts must be at least 1.');
});

it('throws an exception and logs a warning when circuit breaker blocks attempt', function () {
    $breaker = $this->partialMock(CircuitBreaker::class, function (MockInterface $mock) {
        $mock->shouldReceive('canAttempt')->once()->andReturn(false);
        $mock->shouldReceive('getStats')->andReturn([
            'name' => 'test-breaker',
            'state' => 'open',
            'failure_count' => 5,
            'failure_threshold' => 5,
            'opened_at' => now()->timestamp,
            'recovery_timeout' => 60,
        ]);
    });

    $deleter = new FileDeleter(
        $breaker
    );

    Log::spy();

    expect(fn () => $deleter->attemptDelete($this->disk, $this->path))
        ->toThrow(
            \Exception::class,
            'File operations are currently unavailable due to repeated failures. Please try again later.'
        );

    Log::shouldHaveReceived('warning')->once();
});

it('records a circuit breaker failure and throws an exception when deletion fails', function () {
    $diskMock = \Mockery::mock();
    $diskMock->shouldReceive('directoryExists')->andReturnFalse();
    $diskMock->shouldReceive('delete')
        ->andReturn(false);

    Storage::shouldReceive('disk')
        ->andReturn($diskMock);

    Log::spy();

    expect(fn () => $this->deleter->attemptDelete($this->disk, 'path'))
        ->toThrow(\Exception::class, 'Failed to delete file after 3 attempts.');

    Log::shouldHaveReceived('error')->once();
    Log::shouldHaveReceived('warning')->times(3);
});
