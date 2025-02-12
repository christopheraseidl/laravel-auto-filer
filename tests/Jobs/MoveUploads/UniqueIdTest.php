<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\MoveUploads;

use christopheraseidl\HasUploads\Jobs\MoveUploads;

/**
 * Tests that the MoveUploads job generates unique identifiers using the
 * expected format of model_id_operation_attribute.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\MoveUploads
 */
it('returns the correct unique ID', function () {
    $job = new MoveUploads(
        $this->model,
        'string',
        'images'
    );

    $uniqueID = $job->uniqueId();

    expect($uniqueID)->toBe('test_model_1_move_string');
});
