<?php

namespace christopheraseidl\ModelFiler\Tests\TestTraits;

use christopheraseidl\ModelFiler\Jobs\Contracts\DeleteUploadDirectory;
use Illuminate\Support\Facades\Bus;

/**
 * A trait providing re-usable methods for tests in
 * /tests/Handlers/ModelDeletionHandler.
 */
trait AssertsDeleteUploadDirectory
{
    public function assertDeleteUploadDirectoryJobDispatched(): void
    {
        Bus::assertDispatched($this->job::class, function ($job) {
            $payload = $job->getPayload();

            return $job instanceof DeleteUploadDirectory
                && $payload == $this->payload;
        });
    }
}
