<?php

test('getOperationType() returns the correct value', function () {
    $result = $this->job->getOperationType();

    expect($result)->toBe('test_job');
});

test('uniqueId() returns the correct value', function () {
    $result = $this->job->uniqueId();

    expect($result)->toBe(md5('test_job'));
});
