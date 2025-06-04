<?php

namespace christopheraseidl\HasUploads\Tests\TestTraits;

use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager;
use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Handlers\ModelCreationHandler;
use christopheraseidl\HasUploads\Jobs\Contracts\Builder;
use christopheraseidl\HasUploads\Services\Contracts\UploadService;
use christopheraseidl\Reflect\Reflect;

/**
 * A trait providing re-usable methods for tests in
 * /tests/Handlers/ModelCreationHandler.
 */
trait ModelCreationHandlerAssertions
{
    public function setHandler(): void
    {
        $this->handler = Reflect::on(new ModelCreationHandler(
            app(UploadService::class),
            app(Builder::class),
            app(BatchManager::class),
            app(ModelFileChangeTracker::class)
        ));
    }
}
