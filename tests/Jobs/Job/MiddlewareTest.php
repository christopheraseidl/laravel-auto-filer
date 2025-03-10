<?php

use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\ThrottlesExceptions;

test('middleware() returns correct throttling and rate limiting', function () {
    $middleware = $this->job->middleware();

    expect($middleware)
        ->toHaveCount(2)
        ->and($middleware[0])->toBeInstanceOf(ThrottlesExceptions::class)
        ->and($middleware[1])->toBeInstanceOf(RateLimited::class);
});
