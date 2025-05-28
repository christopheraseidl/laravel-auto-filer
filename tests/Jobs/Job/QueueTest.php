<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Job;

/**
 * Tests the Job getQueue method.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Job
 */
it('gets the default queue if it is defined and job is not', function () {
    $name = 'test_default';
    config()->set('has-uploads.default_queue', $name);

    $queue = $this->job->getQueue();

    expect($queue)->toBe($name);
});

it('prefers the job-specific queue over the default queue', function () {
    $default = 'test_default';
    $jobSpecific = 'test_job_specific';
    config()->set('has-uploads.default_queue', $default);
    config()->set('has-uploads.test_job_queue', $jobSpecific);

    $queue = $this->job->getQueue();

    expect($queue)->toBe($jobSpecific);
});

it('returns null if no queue is configured', function () {
    $queue = $this->job->getQueue();

    expect($queue)->toBeNull();
});
