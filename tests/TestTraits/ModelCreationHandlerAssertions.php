<?php

namespace christopheraseidl\ModelFiler\Tests\TestTraits;

use christopheraseidl\ModelFiler\Handlers\Contracts\BatchManager;
use christopheraseidl\ModelFiler\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\ModelFiler\Handlers\ModelCreationHandler;
use christopheraseidl\ModelFiler\Jobs\Contracts\Builder;
use christopheraseidl\ModelFiler\Services\Contracts\FileService;
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
            app(FileService::class),
            app(Builder::class),
            app(BatchManager::class),
            app(ModelFileChangeTracker::class)
        ));
    }
}
