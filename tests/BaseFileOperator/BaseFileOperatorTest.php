<?php

namespace christopheraseidl\AutoFiler\Tests\BaseFileOperator;

use christopheraseidl\AutoFiler\Services\BaseFileOperator;
use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;

it('validates maximum retry attempts configuration', function () {
    config()->set('auto-filer.maximum_file_operation_retries', 0);

    $circuitBreaker = $this->mock(CircuitBreakerContract::class);

    expect(fn () => new class($circuitBreaker) extends BaseFileOperator {})
        ->toThrow(\InvalidArgumentException::class, 'maxAttempts must be at least 1.');
});
