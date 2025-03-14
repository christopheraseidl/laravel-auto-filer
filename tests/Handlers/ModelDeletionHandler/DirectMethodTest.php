<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\ModelDeletionHandler;

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Handlers\ModelDeletionHandler;
use christopheraseidl\HasUploads\Tests\Traits\AssertsDeleteUploadDirectory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

uses(
    DatabaseTransactions::class,
    AssertsDeleteUploadDirectory::class
);

/**
 * Tests ModelDeletionHandler behavior triggered directly by methods.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\ModelDeletionHandler
 */
beforeEach(function () {
    Bus::fake();

    $this->handler = new ModelDeletionHandler(app(UploadService::class));
});

it('dispatches the correctly configured delete upload directory job when handle() is called', function () {
    $this->handler->handle($this->model);

    $this->assertDeleteUploadDirectoryJobDispatched();
});
