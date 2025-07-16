<?php

namespace christopheraseidl\AutoFiler\Tests\FileDeleter;

use christopheraseidl\AutoFiler\Exceptions\FileDeleteException;
use christopheraseidl\AutoFiler\Services\FileDeleterService;
use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->circuitBreaker = $this->mock(CircuitBreakerContract::class);
    $this->service = new FileDeleterService($this->circuitBreaker);

    Storage::fake('public');
    config()->set('auto-filer.disk', 'public');
    config()->set('auto-filer.retry_wait_seconds', 0); // to speed up tests
});

it('deletes files successfully', function () {
    Storage::disk('public')->put('test-file.txt', 'content');

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    $result = $this->service->delete('test-file.txt');

    expect($result)->toBeTrue();
    expect(Storage::disk('public')->exists('test-file.txt'))->toBeFalse();
});

it('deletes directories successfully', function () {
    Storage::disk('public')->put('test-dir/file.txt', 'content');

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    $result = $this->service->delete('test-dir');

    expect($result)->toBeTrue();
    expect(Storage::disk('public')->exists('test-dir'))->toBeFalse();
});

it('handles non-existent files gracefully', function () {
    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    $result = $this->service->delete('non-existent-file.txt');

    expect($result)->toBeTrue();
});

it('records circuit breaker success on successful deletion', function () {
    Storage::disk('public')->put('test-file.txt', 'content');

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    $this->service->delete('test-file.txt');
});

it('records circuit breaker failure on failed deletion', function () {
    Storage::shouldReceive('disk->directoryExists')->andReturnFalse();
    Storage::shouldReceive('disk->delete')->andReturnFalse();

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordFailure')->times(4);

    expect(fn () => $this->service->delete('test-file.txt'))
        ->toThrow(FileDeleteException::class, 'Failed to delete file after 3 attempts.');
});

it('throws FileDeleteException on deletion failure', function () {
    Storage::shouldReceive('disk->directoryExists')->andReturnFalse();
    Storage::shouldReceive('disk->delete')->andReturnFalse();

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordFailure')->times(4);

    expect(fn () => $this->service->delete('test-file.txt'))
        ->toThrow(FileDeleteException::class);
});

it('checks circuit breaker before attempting deletion', function () {
    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnFalse();

    expect(fn () => $this->service->delete('test-file.txt'))
        ->toThrow(\Exception::class);
});
