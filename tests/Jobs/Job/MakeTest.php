<?php

use christopheraseidl\HasUploads\Jobs\Job;
use christopheraseidl\HasUploads\Payloads\Contracts\Payload as PayloadContract;
use christopheraseidl\HasUploads\Tests\TestClasses\TestJob;
use christopheraseidl\HasUploads\Tests\TestClasses\TestJobPayload;

class TestJobWithConstructor extends TestJob
{
    public function __construct(public PayloadContract $payload) {}

    public function getOperationType(): string
    {
        return 'test_job_with_constructor';
    }
}

test('make() returns null when called on an abstract class', function () {
    $result = Job::make(new TestJobPayload);
    expect($result)->toBeNull();
});

test('make() returns instance of a class without a constructor', function () {
    $result = TestJob::make(new TestJobPayload);
    expect($result)->toBeInstanceOf(TestJob::class);
});

test('make() passes payload to concrete class constructor', function () {
    $payload = new TestJobPayload;
    $job = TestJobWithConstructor::make($payload);
    expect($job->getPayload())->toBe($payload);
});
