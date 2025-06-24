<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\CleanOrphanedUploads;

/**
 * Tests the CleanOrphanedUploads handle method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\CleanOrphanedUploads
 */
it('runs when enabled', function () {
    $this->payload->shouldReceive('isCleanupEnabled')->once()->andReturnTrue();

    $this->cleaner->shouldReceive('handleJob')->once();

    $this->cleaner->handle();
});

it('does not run at all when disabled', function () {
    $this->payload->shouldReceive('isCleanupEnabled')->once()->andReturnFalse();

    $this->cleaner->shouldReceive('handleJob')->never();

    $this->cleaner->handle();
});
