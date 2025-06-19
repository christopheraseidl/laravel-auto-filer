<?php

namespace christopheraseidl\ModelFiler\Tests\TestTraits;

use christopheraseidl\ModelFiler\Events\Event;
use christopheraseidl\ModelFiler\Payloads\Contracts\Payload;
use Mockery\MockInterface;

/**
 * A trait providing re-usable methods for tests in /tests/Events.
 */
trait EventHelpers
{
    public function setHandler(): void
    {
        $this->payloadTestValue = $this->mock(Payload::class, function (MockInterface $mock) {
            $mock->shouldReceive('getData')->andReturn(['test' => 'data']);
        });

        $this->handler = new class($this->payloadTestValue) extends Event {};
    }
}
