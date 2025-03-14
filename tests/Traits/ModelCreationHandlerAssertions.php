<?php

namespace christopheraseidl\HasUploads\Tests\Traits;

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager;
use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Handlers\ModelCreationHandler;
use christopheraseidl\HasUploads\Jobs\Contracts\Builder;
use christopheraseidl\Reflect\Reflect;

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
