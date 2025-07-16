<?php

namespace christopheraseidl\AutoFiler\Tests\FileMover;

use christopheraseidl\AutoFiler\Contracts\GenerateThumbnail;
use christopheraseidl\AutoFiler\Exceptions\FileMoveException;
use christopheraseidl\AutoFiler\Exceptions\FileRollbackException;
use christopheraseidl\AutoFiler\Services\FileMoverService;
use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->circuitBreaker = $this->mock(CircuitBreakerContract::class);
    $this->thumbnailGenerator = $this->mock(GenerateThumbnail::class);
    $this->service = new FileMoverService($this->circuitBreaker, $this->thumbnailGenerator);

    Storage::fake('public');
    config()->set('auto-filer.disk', 'public');
    config()->set('auto-filer.max_attempts', 3);
    config()->set('auto-filer.retry_delay', 100);
    config()->set('auto-filer.thumbnails.enabled', false);
    config()->set('auto-filer.retry_wait_seconds', 0); // to speed up tests

    Log::spy();
});

it('rolls back moved files when circuit breaker opens', function () {
    // Set up files
    Storage::disk('public')->put('source/file1.txt', 'content1');
    Storage::disk('public')->put('source/file2.txt', 'content2');

    // Circuit breaker behavior:
    // 1. true - checkCircuitBreaker for first file
    // 2. true - canAttempt in moveWithRetries for first file
    // 3. true - checkCircuitBreaker for second file
    // 4. false - canAttempt in moveWithRetries for second file (triggers failure)
    // 5. true - canAttempt for rollback in undoAllMoves
    // 6. true - canAttempt in undoWithRetries
    $this->circuitBreaker->shouldReceive('canAttempt')
        ->andReturn(true, true, true, false, true, true);

    $this->circuitBreaker->shouldReceive('recordSuccess')->twice(); // First move + rollback

    // Move first file
    $result1 = $this->service->move('source/file1.txt', 'destination/file1.txt');
    expect($result1)->toBe('destination/file1.txt');
    expect(Storage::disk('public')->exists('destination/file1.txt'))->toBeTrue();
    expect(Storage::disk('public')->exists('source/file1.txt'))->toBeFalse();

    // Second file should throw and trigger rollback of first file
    expect(fn () => $this->service->move('source/file2.txt', 'destination/file2.txt'))
        ->toThrow(FileMoveException::class);

    // Verify rollback happened
    expect(Storage::disk('public')->exists('source/file1.txt'))->toBeTrue();
    expect(Storage::disk('public')->exists('destination/file1.txt'))->toBeFalse();
    expect(Storage::disk('public')->exists('source/file2.txt'))->toBeTrue();
    expect(Storage::disk('public')->exists('destination/file2.txt'))->toBeFalse();
});

it('handles rollback when destination file missing', function () {
    // Simulate tracked file that no longer exists at destination
    $reflection = new \ReflectionClass($this->service);
    $movedFiles = $reflection->getProperty('movedFiles');
    $movedFiles->setAccessible(true);
    $movedFiles->setValue($this->service, ['source/file.txt' => 'destination/file.txt']);

    Storage::shouldReceive('disk->exists')->with('destination/file.txt')->andReturnFalse();

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    // Call rollback via reflection
    $method = $reflection->getMethod('attemptUndoMove');
    $method->setAccessible(true);
    $result = $method->invoke($this->service);

    expect($result['successes'])->toHaveKey('source/file.txt');
});

it('handles thumbnail rollback', function () {
    // Set up file
    Storage::disk('public')->put('destination/image-thumb.jpg', 'content');

    // Set up thumbnail tracking
    $reflection = new \ReflectionClass($this->service);
    $movedFiles = $reflection->getProperty('movedFiles');
    $movedFiles->setAccessible(true);
    $movedFiles->setValue($this->service, ['image.jpg_thumb' => 'destination/image-thumb.jpg']);

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    // Call rollback via reflection
    $method = $reflection->getMethod('attemptUndoMove');
    $method->setAccessible(true);
    $result = $method->invoke($this->service);

    expect($result['successes'])->toHaveKey('image.jpg_thumb');
    expect(Storage::disk('public')->exists('destination/image-thumb.jpg'))->toBeFalse();
});

it('clears tracking after successful rollback', function () {
    // Set up file
    Storage::disk('public')->put('destination/file.txt', 'content');

    // Set up file tracking
    $reflection = new \ReflectionClass($this->service);
    $movedFiles = $reflection->getProperty('movedFiles');
    $movedFiles->setAccessible(true);
    $movedFiles->setValue($this->service, ['source/file.txt' => 'destination/file.txt']);

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    // Call rollback via reflection
    $method = $reflection->getMethod('attemptUndoMove');
    $method->setAccessible(true);
    $method->invoke($this->service);

    expect($movedFiles->getValue($this->service))->toBeEmpty();
});

it('throws FileRollbackException on rollback failure', function () {
    // Set up file tracking
    $reflection = new \ReflectionClass($this->service);
    $movedFiles = $reflection->getProperty('movedFiles');
    $movedFiles->setAccessible(true);
    $movedFiles->setValue($this->service, ['source/file.txt' => 'destination/file.txt']);

    Storage::shouldReceive('disk->exists')->with('destination/file.txt')->andReturnTrue();
    Storage::shouldReceive('disk->exists')->with('source/file.txt')->andReturnFalse();
    Storage::shouldReceive('disk->copy')->andThrow(new \Exception('Rollback failed'));

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordFailure')->times(3);

    // Call rollback via reflection
    $method = $reflection->getMethod('attemptUndoMove');
    $method->setAccessible(true);

    expect(fn () => $method->invoke($this->service))
        ->toThrow(FileRollbackException::class);
});

it('handles partial rollback failures', function () {
    // Set up file tracking
    $reflection = new \ReflectionClass($this->service);
    $movedFiles = $reflection->getProperty('movedFiles');
    $movedFiles->setAccessible(true);
    $movedFiles->setValue($this->service, [
        'source/file1.txt' => 'destination/file1.txt',
        'source/file2.txt' => 'destination/file2.txt',
    ]);

    // First file rollback succeeds
    Storage::shouldReceive('disk->exists')->with('destination/file1.txt')->andReturnFalse();

    // Second file rollback fails
    Storage::shouldReceive('disk->exists')->with('destination/file2.txt')->andReturnTrue();
    Storage::shouldReceive('disk->exists')->with('source/file2.txt')->andReturnFalse();
    Storage::shouldReceive('disk->copy')->andThrow(new \Exception('Rollback failed'));

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();
    $this->circuitBreaker->shouldReceive('recordFailure')->times(3);

    // Call rollback via reflection
    $method = $reflection->getMethod('attemptUndoMove');
    $method->setAccessible(true);

    expect(fn () => $method->invoke($this->service))
        ->toThrow(FileRollbackException::class);

    // Check that successful rollback was removed from tracking
    expect($movedFiles->getValue($this->service))
        ->not->toHaveKey('source/file1.txt')
        ->toHaveKey('source/file2.txt');
});
