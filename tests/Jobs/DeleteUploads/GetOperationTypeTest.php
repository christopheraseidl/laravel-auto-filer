<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\DeleteUploads;

use christopheraseidl\HasUploads\Jobs\DeleteUploads;

/**
 * Tests that the DeleteUploads job correctly identifies its operation type as
 * a file delete operation.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\DeleteUploads
 */
it('returns the correct file operation type', function () {
    $job = new DeleteUploads(
        $this->model,
        'string',
        'images'
    );

    $type = $job->getOperationType();

    expect($type)->toBe('delete_file');
});
