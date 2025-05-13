<?php

namespace christopheraseidl\HasUploads\Tests\TestTraits;

use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager;
use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Jobs\Contracts\Builder;
use christopheraseidl\HasUploads\Services\UploadService;
use christopheraseidl\HasUploads\Tests\TestClasses\BaseModelEventHandlerTestClass;

/**
 * A trait providing re-usable methods for tests in
 * /tests/Handlers/BaseModelEventHandler.
 */
trait BaseModelEventHandlerAssertions
{
    public function setHandler(): void
    {
        $this->diskTestValue = 'test_disk';
        $uploadServiceMock = \Mockery::mock(UploadService::class);
        $uploadServiceMock->shouldReceive('getDisk')->andReturn($this->diskTestValue);

        $this->handler = new BaseModelEventHandlerTestClass(
            $uploadServiceMock,
            app(Builder::class),
            app(BatchManager::class),
            app(ModelFileChangeTracker::class)
        );
    }
}
