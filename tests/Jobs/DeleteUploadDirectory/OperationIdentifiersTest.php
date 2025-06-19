<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\DeleteUploadDirectory;

/**
 * Tests the CleanOrphanedUploads getOperationType and uniqueId methods.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\DeleteUploadDirectory
 */
it('returns the expected operation type value', function () {
    expect($this->job->getOperationType())
        ->toBe('delete_directory');
});

it('provides a consistent unique identifier', function () {
    $id1 = $this->job->uniqueId();
    $id2 = $this->job->uniqueId();

    expect($id1)->toBeString()
        ->not->toBeEmpty()
        ->toBe($id2)
        ->toStartWith($this->job->getOperationType());
});
