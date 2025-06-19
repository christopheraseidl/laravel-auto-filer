<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Job;

use christopheraseidl\ModelFiler\Tests\TestClasses\TestJob;
use Illuminate\Support\Facades\Log;

/**
 * Tests the Job config method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Job
 */
beforeEach(function () {
    $this->mock = $this->partialMock(TestJob::class);
});

it('sets a connection if it is defined', function () {
    $this->mock->shouldReceive('getConnection')
        ->once()
        ->andReturn('redis');
    $this->mock->shouldReceive('onConnection');

    $this->mock->config();
});

it('does not set a connection if it is undefined', function () {
    $this->mock->shouldReceive('getConnection')
        ->once()
        ->andReturn(null);
    $this->mock->shouldNotReceive('onConnection');

    $this->mock->config();
});

it('sets a queue if it is defined', function () {
    $this->mock->shouldReceive('getQueue')
        ->once()
        ->andReturn('test-queue');
    $this->mock->shouldReceive('onQueue');

    $this->mock->config();
});

it('does not set a queue if it is undefined', function () {
    $this->mock->shouldReceive('getQueue')
        ->once()
        ->andReturn(null);
    $this->mock->shouldNotReceive('onQueue');

    $this->mock->config();
});

it('throws an exception and logs an error for failures', function () {

    $this->mock->shouldReceive('getConnection')
        ->once()
        ->andThrow(\Exception::class, 'Connection error');

    $exception = false;
    $message = '';
    $expectation = 'The job configuration is invalid';

    Log::spy();

    try {
        $this->mock->config();
    } catch (\Exception $e) {
        $exception = true;
        $message = $e->getMessage();
    }

    expect($exception)->toBeTrue();
    expect($message)->toBe($expectation);

    Log::shouldHaveReceived('error')
        ->with($expectation, [
            'job' => $this->mock::class,
            'error' => 'Connection error',
        ]);
});
