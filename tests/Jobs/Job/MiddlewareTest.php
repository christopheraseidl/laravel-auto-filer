<?php

use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\ThrottlesExceptions;

/**
 * Tests the Job middleware method.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Job
 */
it('returns correct throttling and rate limiting', function () {
    $middleware = $this->job->middleware();

    expect($middleware)->toHaveCount(2);
    expect($middleware[0])->toBeInstanceOf(ThrottlesExceptions::class);
    expect($middleware[1])->toBeInstanceOf(RateLimited::class);
});
