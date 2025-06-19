<?php

namespace christopheraseidl\HasUploads\Tests\TestTraits;

use christopheraseidl\HasUploads\Events\Event;
use christopheraseidl\HasUploads\Payloads\Contracts\Payload;
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
