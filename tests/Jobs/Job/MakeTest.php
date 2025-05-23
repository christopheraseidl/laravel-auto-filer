<?php

use christopheraseidl\HasUploads\Jobs\Job;
use christopheraseidl\HasUploads\Tests\TestClasses\Payload\TestPayloadNoConstructor;
use christopheraseidl\HasUploads\Tests\TestClasses\TestJob;

class TestJobWithConstructor extends TestJob
{
    public function getOperationType(): string
    {
        return 'test_job_with_constructor';
    }
}

test('make() returns null when called on an abstract class', function () {
    $result = Job::make(new TestPayloadNoConstructor);
    expect($result)->toBeNull();
});

test('make() returns instance of a class without a constructor', function () {
    $result = TestJob::make(new TestPayloadNoConstructor);
    expect($result)->toBeInstanceOf(TestJob::class);
});

test('make() passes payload to concrete class constructor', function () {
    $payload = new TestPayloadNoConstructor;
    $job = TestJobWithConstructor::make($payload);
    expect($job->getPayload())->toBe($payload);
});
