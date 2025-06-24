<?php

use christopheraseidl\ModelFiler\Jobs\Contracts\CircuitBreaker;
use christopheraseidl\ModelFiler\Jobs\Services\FileOperator;
use Illuminate\Support\Facades\Log;

// Create a concrete implementation for testing
class TestFileOperator extends FileOperator
{
    public function __construct(CircuitBreaker $breaker)
    {
        $this->breaker = $breaker;
    }
}

beforeEach(function () {
    $this->breaker = $this->mock(CircuitBreaker::class);

    $this->operator = \Mockery::mock(new TestFileOperator($this->breaker))
        ->makePartial();
    $this->operator->shouldReceive('getBreaker')
        ->andReturn($this->breaker);
});

it('can validate max attempts of at least 1', function () {
    expect(fn () => $this->operator->validateMaxAttempts(1))
        ->not->toThrow(InvalidArgumentException::class);

    expect(fn () => $this->operator->validateMaxAttempts(5))
        ->not->toThrow(InvalidArgumentException::class);

    expect(fn () => $this->operator->validateMaxAttempts(0))
        ->toThrow(\InvalidArgumentException::class, 'maxAttempts must be at least 1.');

    expect(fn () => $this->operator->validateMaxAttempts(-1))
        ->toThrow(InvalidArgumentException::class, 'maxAttempts must be at least 1.');
});

it('can check circuit breaker and allow operation when open', function () {
    $this->breaker->shouldReceive('canAttempt')
        ->once()
        ->andReturn(true);

    $this->operator->checkCircuitBreaker('read', 'local', ['file' => 'test.txt']);
});

it('can check circuit breaker and block operation when closed', function () {
    $this->breaker->shouldReceive('canAttempt')
        ->once()
        ->andReturn(false);

    $this->breaker->shouldReceive('getStats')
        ->once()
        ->andReturn(['failures' => 10, 'state' => 'closed']);

    Log::shouldReceive('warning')
        ->once()
        ->with('File operation blocked by circuit breaker.', [
            'operation' => 'write',
            'disk' => 's3',
            'breaker_stats' => ['failures' => 10, 'state' => 'closed'],
            'file' => 'data.csv',
        ]);

    expect(fn () => $this->operator->checkCircuitBreaker('write', 's3', ['file' => 'data.csv']))
        ->toThrow(Exception::class, 'File operations are currently unavailable due to repeated failures. Please try again later.');
});

it('can wait before retry', function () {
    $startTime = microtime(true);

    $this->operator->waitBeforeRetry();

    $elapsedTime = microtime(true) - $startTime;

    // Should wait approximately 1 second (allowing for some variance)
    expect($elapsedTime)->toBeGreaterThanOrEqual(0.9)
        ->and($elapsedTime)->toBeLessThan(1.1);
});
