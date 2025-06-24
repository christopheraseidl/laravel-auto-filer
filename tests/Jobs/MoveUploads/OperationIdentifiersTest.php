<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\MoveUploads;

/**
 * Tests the MoveUploads getOperationType and uniqueId methods.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\MoveUploads
 */
it('returns the expected operation type value', function () {
    expect($this->mover->getOperationType())
        ->toBe('move_file');
});

it('provides a consistent unique identifier', function () {
    $this->payload->shouldReceive('getKey')
        ->andReturn('test_key');

    $id1 = $this->mover->uniqueId();
    $id2 = $this->mover->uniqueId();

    expect($id1)->toBeString()
        ->not->toBeEmpty()
        ->toBe($id2);
});
