<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\CleanOrphanedUploads;

/**
 * Tests the CleanOrphanedUploads getOperationType and uniqueId methods.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\CleanOrphanedUploads
 */
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
