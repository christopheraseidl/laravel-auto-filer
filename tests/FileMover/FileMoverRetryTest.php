<?php

namespace christopheraseidl\AutoFiler\Tests\FileMover;

use christopheraseidl\AutoFiler\Contracts\GenerateThumbnail;
use christopheraseidl\AutoFiler\Exceptions\FileMoveException;
use christopheraseidl\AutoFiler\Services\FileMoverService;
use christopheraseidl\AutoFiler\Tests\Helpers\UsesDiskPartialMock;
use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

uses(UsesDiskPartialMock::class);

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

it('retries move operation and succeeds on second attempt', function () {
    // Set up the file and storage mock
    Storage::disk('public')->put('source/file.txt', 'content');
    $this->partialMockDisk();

    // Mock copy failure on 1st attempt
    $count = 0;
    $this->mockDisk->shouldReceive('copy')
        ->twice()
        ->with('source/file.txt', 'destination/file.txt')
        ->andReturnUsing(function () use (&$count) {
            $count++;
            if ($count === 1) {
                throw new \Exception('Copy failed');
            }
        });

    // Mock copied file validation
    $this->mockDisk->shouldReceive('exists')
        ->with('destination/file.txt')
        ->andReturnTrue();
    $this->mockDisk->shouldReceive('size')
        ->with('destination/file.txt')
        ->andReturn(10);

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordFailure')->once();
    $this->circuitBreaker->shouldReceive('recordSuccess')->once();

    $result = $this->service->move('source/file.txt', 'destination/file.txt');

    expect($result)->toBe('destination/file.txt');
    expect(Storage::disk('public')->exists('destination/file.txt'))->toBeTrue();
    expect(Storage::disk('public')->exists('source/file.txt'))->toBeFalse();
});

it('stops retrying when circuit breaker opens during retry attempts', function () {
    // Set up the file and storage mock
    Storage::disk('public')->put('source/file.txt', 'content');
    $this->partialMockDisk();

    // Mock copy failure
    $count = 0;
    $this->mockDisk->shouldReceive('copy')
        ->twice()
        ->andReturnUsing(function () use (&$count) {
            $count++;

            throw new \Exception('Copy failed');
        });

    $this->circuitBreaker->shouldReceive('canAttempt')
        ->andReturnUsing(function () use (&$count) {
            if ($count === 2) {
                return false; // Open breaker after 2nd copy attempt
            }

            return true;
        });

    $this->circuitBreaker->shouldReceive('recordFailure')->twice();

    expect(fn () => $this->service->move('source/file.txt', 'destination/file.txt'))
        ->toThrow(FileMoveException::class, 'Failed to move file after 3 attempts.');
});

it('logs retry attempts with proper context', function () {
    Storage::disk('public')->put('source/file.txt', 'content');

    Storage::shouldReceive('disk->copy')
        ->times(3)
        ->andThrow(new \Exception('Copy failed'));

    $this->circuitBreaker->shouldReceive('canAttempt')->andReturnTrue();
    $this->circuitBreaker->shouldReceive('recordFailure')->times(3);

    expect(fn () => $this->service->move('source/file.txt', 'destination/file.txt'))
        ->toThrow(FileMoveException::class);

    Log::shouldHaveReceived('warning')
        ->times(3)
        ->with('Move attempt failed.', \Mockery::on(function ($context) {
            return isset($context['attempt'], $context['error']);
        }));
});
