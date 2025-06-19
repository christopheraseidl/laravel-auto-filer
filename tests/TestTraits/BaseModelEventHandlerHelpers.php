<?php

namespace christopheraseidl\ModelFiler\Tests\TestTraits;

use christopheraseidl\ModelFiler\Handlers\Contracts\BatchManager;
use christopheraseidl\ModelFiler\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\ModelFiler\Jobs\Contracts\Builder;
use christopheraseidl\ModelFiler\Services\FileService;
use christopheraseidl\ModelFiler\Tests\TestClasses\BaseModelEventHandlerTestClass;
use Mockery\MockInterface;

/**
 * A trait providing re-usable methods for tests in
 * /tests/Handlers/BaseModelEventHandler.
 */
trait BaseModelEventHandlerHelpers
{
    public function setHandler(): void
    {
        $this->diskTestValue = 'test_disk';

        $this->mock(FileService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getDisk')->andReturn($this->diskTestValue);
        });

        $this->handler = new BaseModelEventHandlerTestClass(
            app(FileService::class),
            app(Builder::class),
            app(BatchManager::class),
            app(ModelFileChangeTracker::class)
        );
    }
}
