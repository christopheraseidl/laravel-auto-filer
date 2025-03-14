<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\CleanOrphanedUploads;

use christopheraseidl\HasUploads\Payloads\CleanOrphanedUploads as CleanOrphanedUploadsPayload;

it('gets the expected payload', function () {
    expect($this->cleaner->getPayload())
        ->toBeInstanceOf(CleanOrphanedUploadsPayload::class)
        ->toBe($this->payload);
});
