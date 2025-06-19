<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Job;

/**
 * Tests the Job getConnection method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Job
 */
it('gets the default connection if it is defined and job is not', function () {
    $name = 'test_default';
    config()->set('model-filer.default_connection', $name);

    $connection = $this->job->getConnection();

    expect($connection)->toBe($name);
});

it('prefers the job-specific connection over the default connection', function () {
    $default = 'test_default';
    $jobSpecific = 'test_job_specific';
    config()->set('model-filer.default_connection', $default);
    config()->set('model-filer.test_job_connection', $jobSpecific);

    $connection = $this->job->getConnection();

    expect($connection)->toBe($jobSpecific);
});

it('returns null if no connection is configured', function () {
    $connection = $this->job->getConnection();

    expect($connection)->toBeNull();
});
