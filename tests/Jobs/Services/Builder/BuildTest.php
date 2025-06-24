<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\Builder;

use christopheraseidl\ModelFiler\Jobs\Contracts\Job;
use christopheraseidl\ModelFiler\Payloads\Contracts\Payload;

/**
 * Tests Builder build method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\Builder
 */
it('builds a job with a given payload', function () {
    $expectedPayload = $this->mock(Payload::class);
    $expectedJob = $this->mock(Job::class);

    $this->builder->shouldReceive('makePayload')
        ->once()
        ->andReturn($expectedPayload);

    $this->builder->shouldReceive('makeJob')
        ->once()
        ->with($expectedPayload)
        ->andReturn($expectedJob);

    $result = $this->builder->build();

    expect($result)->toBe($expectedJob);
});
