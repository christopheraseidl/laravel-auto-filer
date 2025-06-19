<?php

/**
 * Tests the Job getOperationType and uniqueId methods.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Job
 */
it('returns the expected operation type value', function () {
    $result = $this->job->getOperationType();

    expect($result)->toBe('test_job');
});

it('provides a consistent unique identifier', function () {
    $result = $this->job->uniqueId();

    expect($result)->toBe(md5('test_job'));
});
