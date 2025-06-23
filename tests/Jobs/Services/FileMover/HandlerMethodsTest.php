<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

use Illuminate\Support\Facades\Log;

/**
 * Tests FileMover handler methods behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('logs error and throws exception when attempt move fails', function () {
    Log::shouldReceive('error')
        ->once()
        ->with('Failed to move file after 3 attempts.', [
            'disk' => 'test-disk',
            'old_path' => $this->oldPath,
            'new_dir' => $this->newDir,
            'max_attempts' => 3,
            'last_error' => 'Original error message',
        ]);

    $originalException = new \Exception('Original error message');

    expect(fn () => $this->mover->handleCaughtAttemptMoveException(
        'test-disk',
        $this->oldPath,
        $this->newDir,
        3,
        $originalException
    ))->toThrow(\Exception::class, 'Failed to move file after 3 attempts.');
});

it('re-throws exception when message matches expected message', function () {
    $exception = new \Exception('Custom error message');

    expect(fn () => $this->mover->handleCaughtStorageException($exception, 'Custom error message'))
        ->toThrow(\Exception::class, 'Custom error message');

    $this->breaker->shouldNotHaveReceived('recordFailure');
});

it('records failure and wraps exception when message does not match', function () {
    $this->breaker->shouldReceive('recordFailure')
        ->once();

    $originalException = new \Exception('Original error');

    expect(fn () => $this->mover->handleCaughtStorageException($originalException, 'Custom message'))
        ->toThrow(\Exception::class, 'Custom message. Original error');
});

it('logs warning when move attempt fails and max attempts not reached', function () {
    Log::shouldReceive('warning')
        ->once()
        ->with('Move attempt failed.', [
            'attempt' => 2,
            'error' => 'Test error message',
        ]);

    $this->breaker->shouldReceive('maxAttemptsReached')
        ->once()
        ->with(2, 5)
        ->andReturnFalse();

    $this->breaker->shouldReceive('canAttempt')
        ->once()
        ->andReturnTrue();

    $this->mover->shouldReceive('waitBeforeRetry')
        ->once();

    $this->mover->handleProcessMoveCaughtException('test-disk', 2, 5, 'Test error message');
});

it('attempts undo when max attempts reached and files were moved', function () {
    Log::shouldReceive('warning')
        ->once()
        ->with('Move attempt failed.', [
            'attempt' => 5,
            'error' => 'Test error message',
        ]);

    $this->breaker->shouldReceive('maxAttemptsReached')
        ->once()
        ->with(5, 5)
        ->andReturnTrue();

    $this->mover->shouldReceive('getMovedFiles')
        ->once()
        ->andReturn(['file1.txt', 'file2.txt']);

    $this->mover->shouldReceive('attemptUndoMove')
        ->once()
        ->with('test-disk', 5);

    $this->mover->handleProcessMoveCaughtException('test-disk', 5, 5, 'Test error message');
});

it('logs error when undo fails during process move exception handling', function () {
    Log::shouldReceive('warning')->once();
    Log::shouldReceive('error')
        ->once()
        ->with('Unexpected exception during undo after move failure.', [
            'disk' => 'test-disk',
            'error' => 'Undo failed',
        ]);

    $this->breaker->shouldReceive('maxAttemptsReached')
        ->once()
        ->andReturnTrue();

    $this->mover->shouldReceive('getMovedFiles')
        ->once()
        ->andReturn(['file1.txt']);

    $this->mover->shouldReceive('attemptUndoMove')
        ->once()
        ->andThrow(new \Exception('Undo failed'));

    $this->mover->handleProcessMoveCaughtException('test-disk', 5, 5, 'Test error message');
});

it('records failure when max attempts reached in process move failure', function () {
    $this->breaker->shouldReceive('maxAttemptsReached')
        ->once()
        ->with(3, 3)
        ->andReturnTrue();

    $this->breaker->shouldReceive('recordFailure')
        ->once();

    $exception = new \Exception('Custom error');

    expect(fn () => $this->mover->handleProcessMoveFailure(3, 3, $exception))
        ->toThrow(\Exception::class, 'Custom error');
});

it('throws default exception when no exception provided in process move failure', function () {
    $this->breaker->shouldReceive('maxAttemptsReached')
        ->once()
        ->with(2, 3)
        ->andReturnFalse();

    expect(fn () => $this->mover->handleProcessMoveFailure(2, 3))
        ->toThrow(\Exception::class, 'Move failed without exception');
});

it('logs warning and waits when undo attempt fails but attempts remain', function () {
    Log::shouldReceive('warning')
        ->once()
        ->with('Undo attempt failed.', [
            'disk' => 'test-disk',
            'attempt' => 2,
            [
                'old_path' => $this->oldPath,
                'new_path' => $this->newPath,
            ],
            'error' => 'Undo error',
        ]);

    $this->mover->shouldReceive('waitBeforeRetry')
        ->once();

    $exception = new \Exception('Undo error');

    $this->mover->handleCaughtProcessSingleUndoException(
        'test-disk',
        $this->oldPath,
        $this->newPath,
        2,
        5,
        $exception
    );
});

it('logs warning when circuit breaker blocks undo operations', function () {
    Log::shouldReceive('warning')
        ->once()
        ->with('File move undo blocked by circuit breaker', [
            'disk' => 'test-disk',
            'pending_undos' => 3,
        ]);

    $this->mover->shouldReceive('getMovedFiles')
        ->once()
        ->andReturn(['file1.txt', 'file2.txt', 'file3.txt']);

    $this->mover->handleCircuitBreakerBlock('test-disk');
});

it('clears moved files when undo succeeds', function () {
    $this->mover->shouldReceive('clearMovedFiles')
        ->once();

    $this->mover->handleAttemptUndoSuccess();
});

it('records failure and throws exception when undo fails', function () {
    $this->breaker->shouldReceive('recordFailure')
        ->once();

    Log::shouldReceive('error')
        ->once()
        ->with('File move undo failure.', [
            'disk' => 'test-disk',
            'failed' => ['file1.txt'],
            'succeeded' => ['file2.txt'],
        ]);

    $this->mover->shouldReceive('uncommitSuccessfulUndos')
        ->once()
        ->with(['file2.txt']);

    $results = [
        'failures' => ['file1.txt'],
        'successes' => ['file2.txt'],
    ];

    expect(fn () => $this->mover->handleAttemptUndoFailure('test-disk', $results, true))
        ->toThrow(\Exception::class, 'Failed to undo 1 file move(s):');
});

it('does not throw when undo fails but throwOnFailure is false', function () {
    $this->breaker->shouldReceive('recordFailure')->once();
    Log::shouldReceive('error')->once();
    $this->mover->shouldReceive('uncommitSuccessfulUndos')->once();

    $results = [
        'failures' => ['file1.txt'],
        'successes' => ['file2.txt'],
    ];

    $this->mover->handleAttemptUndoFailure('test-disk', $results, false);

    // Test passes if no exception is thrown
    expect(true)->toBeTrue();
});
