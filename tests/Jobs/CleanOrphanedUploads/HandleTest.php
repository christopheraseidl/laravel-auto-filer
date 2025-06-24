<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\CleanOrphanedUploads;

/**
 * Tests the CleanOrphanedUploads handle() method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\CleanOrphanedUploads
 */
it('does not run at all when disabled', function () {
    $this->payload->shouldReceive('isCleanupEnabled')->once()->andReturnFalse();

    $this->cleaner->shouldReceive('handleJob')->never();

    $this->cleaner->handle();
});
