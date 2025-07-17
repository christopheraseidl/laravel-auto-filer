<?php

namespace christopheraseidl\AutoFiler\Tests\FileMover;

use christopheraseidl\AutoFiler\Contracts\GenerateThumbnail;
use christopheraseidl\AutoFiler\Exceptions\FileMoveException;
use christopheraseidl\AutoFiler\Services\FileMoverService;
use christopheraseidl\AutoFiler\Tests\Helpers\UsesDiskPartialMock;
use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;
use christopheraseidl\CircuitBreaker\Exceptions\CircuitBreakerException;
use Illuminate\Support\Facades\Storage;

uses(UsesDiskPartialMock::class);

beforeEach(function () {
    $this->circuitBreaker = $this->mock(CircuitBreakerContract::class);
    $this->thumbnailGenerator = $this->mock(GenerateThumbnail::class);
    $this->service = new FileMoverService($this->circuitBreaker, $this->thumbnailGenerator);

    Storage::fake('public');
    config()->set('auto-filer.disk', 'public');
    config()->set('auto-filer.max_attempts', 3);
    config()->set('auto-filer.thumbnails.enabled', false);
    config()->set('auto-filer.retry_wait_seconds', 0); // to speed up tests
});

it('moves files successfully using copy and delete', function () {
    Storage::disk('public')->put('source/file.txt', 'content');

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    $result = $this->service->move('source/file.txt', 'destination/file.txt');

    expect($result)->toBe('destination/file.txt');
    expect(Storage::disk('public')->exists('destination/file.txt'))->toBeTrue();
    expect(Storage::disk('public')->exists('source/file.txt'))->toBeFalse();
    expect(Storage::disk('public')->get('destination/file.txt'))->toBe('content');
});

it('validates copied file exists at destination', function () {
    Storage::disk('public')->put('source/file.txt', 'content');

    // Mock copied file existence check failure
    $this->partialMockDisk();
    $this->mockDisk->shouldReceive('exists')
        ->with('destination/file.txt')
        ->andReturnFalse();

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordFailure')->times(3);

    expect(fn () => $this->service->move('source/file.txt', 'destination/file.txt'))
        ->toThrow(FileMoveException::class, 'Failed to move file after 3 attempts.');
});

it('validates copied file has content', function () {
    Storage::disk('public')->put('source/file.txt', 'content');

    // Mock copied file size being 0
    $this->partialMockDisk();
    $this->mockDisk->shouldReceive('size')
        ->with('destination/file.txt')
        ->andReturn(0);

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordFailure')->times(3);

    expect(fn () => $this->service->move('source/file.txt', 'destination/file.txt'))
        ->toThrow(FileMoveException::class, 'Failed to move file after 3 attempts.');
});

it('tracks moved files for rollback', function () {
    Storage::disk('public')->put('source/file.txt', 'content');

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    $this->service->move('source/file.txt', 'destination/file.txt');

    // Access private property using reflection for testing
    $reflection = new \ReflectionClass($this->service);
    $movedFiles = $reflection->getProperty('movedFiles');
    $movedFiles->setAccessible(true);

    expect($movedFiles->getValue($this->service))
        ->toHaveKey('source/file.txt')
        ->and($movedFiles->getValue($this->service))
        ->toContain('destination/file.txt');
});

it('records circuit breaker success on successful move', function () {
    Storage::disk('public')->put('source/file.txt', 'content');

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    $this->service->move('source/file.txt', 'destination/file.txt');
});

it('throws FileMoveException on move failure', function () {
    Storage::shouldReceive('disk->copy')->andThrow(new \Exception('Copy failed'));

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();

    expect(fn () => $this->service->move('source/file.txt', 'destination/file.txt'))
        ->toThrow(FileMoveException::class);
});

it('checks circuit breaker before attempting move', function () {
    $this->circuitBreaker->shouldReceive('canAttempt')
        ->once()
        ->andReturnFalse();
    $this->circuitBreaker->shouldReceive('getStats')->once();

    expect(fn () => $this->service->move('source/file.txt', 'destination/file.txt'))
        ->toThrow(CircuitBreakerException::class, 'File operations are currently unavailable due to repeated failures. Please try again later.');
});

it('handles source file not existing', function () {
    Storage::shouldReceive('disk->copy')->andThrow(new \Exception('Source file not found'));

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();

    expect(fn () => $this->service->move('non-existent.txt', 'destination/file.txt'))
        ->toThrow(FileMoveException::class, 'Failed to move file after 3 attempts.');
});

it('cleans up on source deletion failure', function () {
    config()->set('auto-filer.thumbnails.enabled', true);

    // Set up the file and storage mock
    Storage::disk('public')->put('source/image.jpg', 'content');
    $this->partialMockDisk();

    // Thumbnail generation
    $this->thumbnailGenerator->shouldReceive('__invoke')
        ->with('destination/image.jpg')
        ->times(3)
        ->andReturnUsing(function () {
            Storage::disk('public')->put('destination/image-thumb.jpg', 'content');

            return [
                'success' => true,
                'path' => 'destination/image-thumb.jpg',
            ];
        });

    // Deletion of source file fails
    $this->mockDisk->shouldReceive('delete')
        ->with('source/image.jpg')
        ->times(3)
        ->andThrow(new \Exception('Delete failed'));

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    // 3 failures for the move attempt, 3 for the undo attempt
    $this->circuitBreaker->shouldReceive('recordFailure')->times(6);

    expect(fn () => $this->service->move('source/image.jpg', 'destination/image.jpg'))
        ->toThrow(FileMoveException::class);

    // Verify cleanup of main image
    expect(Storage::disk('public')->exists('source/image.jpg'))->toBeTrue();
    expect(Storage::disk('public')->exists('destination/image.jpg'))->toBeFalse();

    // Verify thumbnail cleanup: 3 calls in move, 3 in rollback
    $this->mockDisk->shouldHaveReceived('delete')
        ->with('destination/image-thumb.jpg')
        ->times(6);

    expect(Storage::disk('public')->exists('destination/image-thumb.jpg'))->toBeFalse();
});
