<?php

namespace christopheraseidl\ModelFiler\Tests\Handlers\ModelDeletionHandler;

use christopheraseidl\ModelFiler\Tests\TestTraits\AssertsDeleteUploadDirectory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

uses(
    DatabaseTransactions::class,
    AssertsDeleteUploadDirectory::class
);

/**
 * Tests ModelDeletionHandler behavior triggered by the deletion event.
 *
 * @covers \christopheraseidl\ModelFiler\Handlers\ModelDeletionHandler
 */
beforeEach(function () {
    Bus::fake();

    $this->model->delete();
});

it('dispatches the correctly configured delete upload directory job on model deletion', function () {
    $this->assertDeleteUploadDirectoryJobDispatched();
});
