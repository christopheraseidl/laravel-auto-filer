<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\MoveUploads;

use christopheraseidl\HasUploads\Jobs\MoveUploads;

/**
 * Tests that the MoveUploads job correctly identifies its operation type as a
 * file move operation.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\MoveUploads
 */
it('returns the correct file operation type', function () {
    $job = new MoveUploads(
        $this->model,
        'string',
        'images'
    );

    $type = $job->getOperationType();

    expect($type)->toBe('move_file');
});
