<?php

namespace christopheraseidl\ModelFiler\Tests\Handlers\ModelDeletionHandler;

use christopheraseidl\ModelFiler\Handlers\ModelDeletionHandler;
use christopheraseidl\ModelFiler\Services\Contracts\FileService;
use christopheraseidl\ModelFiler\Tests\TestTraits\AssertsDeleteUploadDirectory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

uses(
    DatabaseTransactions::class,
    AssertsDeleteUploadDirectory::class
);

/**
 * Tests ModelDeletionHandler behavior triggered directly by methods.
 *
 * @covers \christopheraseidl\ModelFiler\Handlers\ModelDeletionHandler
 */
beforeEach(function () {
    Bus::fake();

    $this->handler = new ModelDeletionHandler(app(FileService::class));
});

it('dispatches the correctly configured delete upload directory job when handle() is called', function () {
    // See Pest.php for arrangement of test conditions
    $this->handler->handle($this->model);

    $this->assertDeleteUploadDirectoryJobDispatched();
});
