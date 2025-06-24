<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\Builder;

use christopheraseidl\ModelFiler\Payloads\Payload;

/**
 * Tests Builder makeJob method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\Builder
 */
it('returns job instance from container', function () {
    $payload = $this->mock(Payload::class);

    $this->builder->shouldReceive('getJobClass')
        ->once()
        ->andReturn($this->jobClass);

    $result = $this->builder->makeJob($payload);

    expect($result)->toBeInstanceOf($this->jobClass);
});
