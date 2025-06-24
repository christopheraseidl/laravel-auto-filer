<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\DeleteUploadDirectory;

/**
 * Tests the CleanOrphanedUploads getOperationType and uniqueId methods.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\DeleteUploadDirectory
 */
it('returns the expected operation type value', function () {
    expect($this->deleter->getOperationType())
        ->toBe('delete_directory');
});

it('provides a consistent unique identifier', function () {
    $this->payload->shouldReceive('getModelClass')->andReturn($this->model::class);
    $this->payload->shouldReceive('getId')->andReturn($this->model->id);

    $id1 = $this->deleter->uniqueId();
    $id2 = $this->deleter->uniqueId();

    expect($id1)->toBeString()
        ->not->toBeEmpty()
        ->toBe($id2);
});
