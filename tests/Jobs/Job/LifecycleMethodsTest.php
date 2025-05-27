<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Tests the Job retryUntil and failed methods.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Job
 */
test('retryUntil() returns DateTime 5 minutes from now', function () {
    Carbon::setTestNow(now());

    $retryUntil = $this->job->retryUntil();
    $time = now()->addMinutes(5);

    expect($retryUntil)->toEqual($time);
});

test('failed() logs operation type and payload', function () {
    Log::spy();

    $this->job->failed(new \Exception('Job failed.'));

    Log::shouldHaveReceived('error')
        ->with('Job failed: test_job.', ['key' => 'value']);
});
