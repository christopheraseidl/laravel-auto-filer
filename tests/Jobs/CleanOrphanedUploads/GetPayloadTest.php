<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\CleanOrphanedUploads;

use christopheraseidl\ModelFiler\Jobs\CleanOrphanedUploads;
use christopheraseidl\ModelFiler\Payloads\CleanOrphanedUploads as CleanOrphanedUploadsPayload;

/**
 * Tests the CleanOrphanedUploads getPayload method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\CleanOrphanedUploads
 */
it('gets the expected payload', function () {
    $payload = new CleanOrphanedUploadsPayload(
        $this->disk,
        $this->path,
        24
    );

    $cleaner = new CleanOrphanedUploads($payload);

    expect($cleaner->getPayload())
        ->toBeInstanceOf(CleanOrphanedUploadsPayload::class)
        ->toBe($payload);
});
