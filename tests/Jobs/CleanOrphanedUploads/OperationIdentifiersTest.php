<?php

/**
 * Tests the CleanOrphanedUploads getOperationType method.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\CleanOrphanedUploads
 */

use christopheraseidl\HasUploads\Jobs\CleanOrphanedUploads;

it('returns the expected operation type value', function () {
    expect($this->cleaner->getOperationType())->toBe('clean_directory');
});

it('provides a consistent unique identifier', function () {
    $id1 = $this->cleaner->uniqueId();
    $id2 = $this->cleaner->uniqueId();

    expect($id1)->toBeString()
        ->not->toBeEmpty()
        ->toBe($id2);
});
