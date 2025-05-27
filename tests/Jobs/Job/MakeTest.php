<?php

use christopheraseidl\HasUploads\Jobs\Job;
use christopheraseidl\HasUploads\Tests\TestClasses\Payload\TestPayloadNoConstructor;
use christopheraseidl\HasUploads\Tests\TestClasses\TestJob;
use christopheraseidl\HasUploads\Tests\TestClasses\TestJobWithoutConstructor;
use christopheraseidl\Reflect\Reflect;

/**
 * Tests the Job make method.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Job
 */
class TestJobWithConstructor extends TestJob
{
    public function getOperationType(): string
    {
        return 'test_job_with_constructor';
    }
}

it('returns null when called on an abstract class', function () {
    $result = Job::make(new TestPayloadNoConstructor);

    expect($result)->toBeNull();
});

it('returns instance of a job child class with a constructor', function () {
    $result = TestJob::make(new TestPayloadNoConstructor);

    expect($result)->toBeInstanceOf(TestJob::class);
});

it('returns instance of a job child class without a constructor', function () {
    $result = TestJobWithoutConstructor::make(new TestPayloadNoConstructor);

    expect($result)->toBeInstanceOf(TestJobWithoutConstructor::class);
});

it('passes payload to concrete class constructor', function () {
    $payload = new TestPayloadNoConstructor;
    $job = TestJobWithConstructor::make($payload);

    expect(Reflect::on($job)->payload)->toBe($payload);
});
