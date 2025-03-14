<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\ModelDeletionHandler;

use christopheraseidl\HasUploads\Tests\Traits\AssertsDeleteUploadDirectory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

uses(
    DatabaseTransactions::class,
    AssertsDeleteUploadDirectory::class
);

/**
 * Tests ModelDeletionHandler behavior triggered by the deletion event.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\ModelDeletionHandler
 */
beforeEach(function () {
    Bus::fake();

    $this->model->delete();
});

it('dispatches the correctly configured delete upload directory job on model deletion', function () {
    $this->assertDeleteUploadDirectoryJobDispatched();
});
