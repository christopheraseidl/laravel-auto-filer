<?php

namespace christopheraseidl\AutoFiler\Tests\FileMover;

use christopheraseidl\AutoFiler\Contracts\GenerateThumbnail;
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

it('rolls back moved files on failure', function () {
    // Set up initial successful move followed by failure
    Storage::disk('public')->put('source/file1.txt', 'content1');
    Storage::disk('public')->put('source/file2.txt', 'content2');
    
    // Mock first move succeeds, copy works, but delete fails causing rollback
    Storage::shouldReceive('disk->copy')->with('source/file1.txt', 'destination/file1.txt')->once();
    Storage::shouldReceive('disk->exists')->with('destination/file1.txt')->andReturnTrue();
    Storage::shouldReceive('disk->size')->with('destination/file1.txt')->andReturn(100);
    Storage::shouldReceive('disk->delete')->with('source/file1.txt')->andThrow(new \Exception('Delete failed'));
    
    // Rollback operations
    Storage::shouldReceive('disk->exists')->with('destination/file1.txt')->andReturnTrue();
    Storage::shouldReceive('disk->exists')->with('source/file1.txt')->andReturnFalse();
    Storage::shouldReceive('disk->copy')->with('destination/file1.txt', 'source/file1.txt')->once();
    Storage::shouldReceive('disk->exists')->with('source/file1.txt')->andReturnTrue();
    Storage::shouldReceive('disk->size')->with('source/file1.txt')->andReturn(100);
    Storage::shouldReceive('disk->delete')->with('destination/file1.txt')->once();
    
    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once(); // for rollback success
    
    expect(fn() => $this->service->move('source/file1.txt', 'destination/file1.txt'))
        ->toThrow(\Exception::class);
});

// it('handles rollback when destination file missing', function () {
//     // Simulate tracked file that no longer exists at destination
//     $reflection = new \ReflectionClass($this->service);
//     $movedFiles = $reflection->getProperty('movedFiles');
//     $movedFiles->setAccessible(true);
//     $movedFiles->setValue($this->service, ['source/file.txt' => 'destination/file.txt']);
    
//     Storage::shouldReceive('disk->exists')->with('destination/file.txt')->andReturnFalse();
    
//     $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
//     $this->circuitBreaker->shouldReceive('recordSuccess')->once();
    
//     // Call rollback via reflection
//     $method = $reflection->getMethod('attemptUndoMove');
//     $method->setAccessible(true);
//     $result = $method->invoke($this->service);
    
//     expect($result['successes'])->toHaveKey('source/file.txt');
// });

// it('handles thumbnail rollback', function () {
//     // Set up thumbnail tracking
//     $reflection = new \ReflectionClass($this->service);
//     $movedFiles = $reflection->getProperty('movedFiles');
//     $movedFiles->setAccessible(true);
//     $movedFiles->setValue($this->service, ['image.jpg_thumb' => 'destination/thumb.jpg']);
    
//     Storage::shouldReceive('disk->exists')->with('destination/thumb.jpg')->andReturnTrue();
//     Storage::shouldReceive('disk->delete')->with('destination/thumb.jpg')->once();
    
//     $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
//     $this->circuitBreaker->shouldReceive('recordSuccess')->once();
    
//     // Call rollback via reflection
//     $method = $reflection->getMethod('attemptUndoMove');
//     $method->setAccessible(true);
//     $result = $method->invoke($this->service);
    
//     expect($result['successes'])->toHaveKey('image.jpg_thumb');
// });

// it('clears tracking after successful rollback', function () {
//     $reflection = new \ReflectionClass($this->service);
//     $movedFiles = $reflection->getProperty('movedFiles');
//     $movedFiles->setAccessible(true);
//     $movedFiles->setValue($this->service, ['source/file.txt' => 'destination/file.txt']);
    
//     Storage::shouldReceive('disk->exists')->with('destination/file.txt')->andReturnFalse();
    
//     $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
//     $this->circuitBreaker->shouldReceive('recordSuccess')->once();
    
//     // Call rollback via reflection
//     $method = $reflection->getMethod('attemptUndoMove');
//     $method->setAccessible(true);
//     $method->invoke($this->service);
    
//     expect($movedFiles->getValue($this->service))->toBeEmpty();
// });

// it('throws FileRollbackException on rollback failure', function () {
//     $reflection = new \ReflectionClass($this->service);
//     $movedFiles = $reflection->getProperty('movedFiles');
//     $movedFiles->setAccessible(true);
//     $movedFiles->setValue($this->service, ['source/file.txt' => 'destination/file.txt']);
    
//     Storage::shouldReceive('disk->exists')->with('destination/file.txt')->andReturnTrue();
//     Storage::shouldReceive('disk->exists')->with('source/file.txt')->andReturnFalse();
//     Storage::shouldReceive('disk->copy')->andThrow(new \Exception('Rollback failed'));
    
//     $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
//     $this->circuitBreaker->shouldReceive('recordFailure')->once();
    
//     // Call rollback via reflection
//     $method = $reflection->getMethod('attemptUndoMove');
//     $method->setAccessible(true);
    
//     expect(fn() => $method->invoke($this->service))
//         ->toThrow(FileRollbackException::class);
// });

// it('handles partial rollback failures', function () {
//     $reflection = new \ReflectionClass($this->service);
//     $movedFiles = $reflection->getProperty('movedFiles');
//     $movedFiles->setAccessible(true);
//     $movedFiles->setValue($this->service, [
//         'source/file1.txt' => 'destination/file1.txt',
//         'source/file2.txt' => 'destination/file2.txt'
//     ]);
    
//     // First file rollback succeeds
//     Storage::shouldReceive('disk->exists')->with('destination/file1.txt')->andReturnFalse();
    
//     // Second file rollback fails
//     Storage::shouldReceive('disk->exists')->with('destination/file2.txt')->andReturnTrue();
//     Storage::shouldReceive('disk->exists')->with('source/file2.txt')->andReturnFalse();
//     Storage::shouldReceive('disk->copy')->andThrow(new \Exception('Rollback failed'));
    
//     $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
//     $this->circuitBreaker->shouldReceive('recordSuccess')->once();
//     $this->circuitBreaker->shouldReceive('recordFailure')->once();
    
//     // Call rollback via reflection
//     $method = $reflection->getMethod('attemptUndoMove');
//     $method->setAccessible(true);
    
//     expect(fn() => $method->invoke($this->service))
//         ->toThrow(FileRollbackException::class);
    
//     // Check that successful rollback was removed from tracking
//     expect($movedFiles->getValue($this->service))
//         ->not->toHaveKey('source/file1.txt')
//         ->toHaveKey('source/file2.txt');
// });
