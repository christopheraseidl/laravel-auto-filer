<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\CleanOrphanedUploads;

use christopheraseidl\HasUploads\Payloads\CleanOrphanedUploads as CleanOrphanedUploadsPayload;

/**
 * Tests the CleanOrphanedUploads getPayload method.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\CleanOrphanedUploads
 */
it('gets the expected payload', function () {
    expect($this->cleaner->getPayload())
        ->toBeInstanceOf(CleanOrphanedUploadsPayload::class)
        ->toBe($this->payload);
});
