<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Job;

/**
 * Tests the Job getConnection method.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Job
 */
it('gets the default connection if it is defined and job is not', function () {
    $name = 'test_default';
    config()->set('has-uploads.default_connection', $name);

    $connection = $this->job->getConnection();

    expect($connection)->toBe($name);
});

it('prefers the job-specific connection over the default connection', function () {
    $default = 'test_default';
    $jobSpecific = 'test_job_specific';
    config()->set('has-uploads.default_connection', $default);
    config()->set('has-uploads.test_job_connection', $jobSpecific);

    $connection = $this->job->getConnection();

    expect($connection)->toBe($jobSpecific);
});

it('returns null if no connection is configured', function () {
    $connection = $this->job->getConnection();

    expect($connection)->toBeNull();
});
