<?php

namespace christopheraseidl\AutoFiler\Tests\FileMover;

use christopheraseidl\AutoFiler\Contracts\GenerateThumbnail;
use christopheraseidl\AutoFiler\Exceptions\FileMoveException;
use christopheraseidl\AutoFiler\Services\FileMoverService;
use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;
use christopheraseidl\CircuitBreaker\Exceptions\CircuitBreakerException;
use Illuminate\Support\Facades\Storage;

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
    Storage::shouldReceive('disk->copy')->times(3);
    Storage::shouldReceive('disk->exists')->with('destination/file.txt')->andReturnFalse();

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordFailure')->times(3);

    expect(fn () => $this->service->move('source/file.txt', 'destination/file.txt'))
        ->toThrow(FileMoveException::class, 'Failed to move file after 3 attempts.');
});

it('validates copied file has content', function () {
    Storage::disk('public')->put('source/file.txt', 'content');

    // Mock copied file size being 0
    Storage::shouldReceive('disk->copy')->times(3);
    Storage::shouldReceive('disk->exists')->with('destination/file.txt')->andReturnTrue();
    Storage::shouldReceive('disk->size')->with('destination/file.txt')->andReturn(0);

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
