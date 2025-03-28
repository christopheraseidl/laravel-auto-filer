<?php

namespace christopheraseidl\HasUploads\Tests\TestTraits;

use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploadDirectory;
use Illuminate\Support\Facades\Bus;

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
