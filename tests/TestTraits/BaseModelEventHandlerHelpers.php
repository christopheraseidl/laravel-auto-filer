<?php

namespace christopheraseidl\HasUploads\Tests\TestTraits;

use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager;
use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Jobs\Contracts\Builder;
use christopheraseidl\HasUploads\Services\UploadService;
use christopheraseidl\HasUploads\Tests\TestClasses\BaseModelEventHandlerTestClass;
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

        $this->mock(UploadService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getDisk')->andReturn($this->diskTestValue);
        });

        $this->handler = new BaseModelEventHandlerTestClass(
            app(UploadService::class),
            app(Builder::class),
            app(BatchManager::class),
            app(ModelFileChangeTracker::class)
        );
    }
}
