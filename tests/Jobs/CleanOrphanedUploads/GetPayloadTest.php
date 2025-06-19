<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\CleanOrphanedUploads;

use christopheraseidl\ModelFiler\Payloads\CleanOrphanedUploads as CleanOrphanedUploadsPayload;

/**
 * Tests the CleanOrphanedUploads getPayload method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\CleanOrphanedUploads
 */
it('gets the expected payload', function () {
    expect($this->cleaner->getPayload())
        ->toBeInstanceOf(CleanOrphanedUploadsPayload::class)
        ->toBe($this->payload);
});
