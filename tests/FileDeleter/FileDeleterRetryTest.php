<?php

namespace christopheraseidl\AutoFiler\Tests\FileDeleter;

use christopheraseidl\AutoFiler\Exceptions\FileDeleteException;
use christopheraseidl\AutoFiler\Services\FileDeleterService;
use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->circuitBreaker = $this->mock(CircuitBreakerContract::class);
    $this->service = new FileDeleterService($this->circuitBreaker);
    
    Storage::fake('public');
    config()->set('auto-filer.disk', 'public');
    config()->set('auto-filer.max_attempts', 3);
    config()->set('auto-filer.retry_delay', 0);
    config()->set('auto-filer.retry_wait_seconds', 0); // to speed up tests
    
    Log::spy();
});

it('retries failed deletions up to max attempts', function () {
    Storage::shouldReceive('disk->directoryExists')->andReturnFalse();
    Storage::shouldReceive('disk->delete')
        ->times(3)
        ->andReturnFalse();
    
    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordFailure')->times(3);
    
    expect(fn() => $this->service->delete('test-file.txt'))
        ->toThrow(FileDeleteException::class, 'Failed to delete file after 3 attempts.');
});

it('stops retrying when circuit breaker blocks', function () {
    Storage::shouldReceive('disk->directoryExists')
        ->once()
        ->andReturnFalse();
    Storage::shouldReceive('disk->delete')
        ->once()
        ->andReturnFalse();

    $count = 0;
    
    $this->circuitBreaker->shouldReceive('canAttempt')
        ->times(4)
        ->andReturnUsing(function () use (&$count) {
            $count++;

            if ($count === 4) {
                return false;
            }

            return true;
        });
    $this->circuitBreaker->shouldReceive('recordFailure')->once();
    
    expect(fn() => $this->service->delete('test-file.txt'))
        ->toThrow(FileDeleteException::class);
});

it('logs failures and throws final exception', function () {
    Storage::shouldReceive('disk->directoryExists')->andReturnFalse();
    Storage::shouldReceive('disk->delete')
        ->times(3)
        ->andReturnFalse();
    
    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordFailure')->times(3);
    
    expect(fn() => $this->service->delete('test-file.txt'))
        ->toThrow(FileDeleteException::class, 'Failed to delete file after 3 attempts.');
    
    // Logs warnings during attempts
    Log::shouldHaveReceived('warning')
        ->times(3);

    // Logs a final error when attempts have run out
    Log::shouldHaveReceived('error')
        ->once()
        ->with('File deletion failed after 3 attempts.', \Mockery::type('array'));
});

it('succeeds on retry attempt', function () {
    Storage::shouldReceive('disk->directoryExists')->andReturnFalse();

    $count = 0;

    Storage::shouldReceive('disk->delete')
        ->twice()
        ->andReturnUsing(function () use (&$count) {
            $count++;

            if ($count === 2) {
                return true; // Succeed the second time
            }

            return false;
        });
    
    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();
    
    $result = $this->service->delete('test-file.txt');
    
    expect($result)->toBeTrue();
    
    Log::shouldHaveReceived('warning')
        ->once()
        ->with('File delete attempt 1 failed.', \Mockery::type('array'));
});
