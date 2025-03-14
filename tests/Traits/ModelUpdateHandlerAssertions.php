<?php

namespace christopheraseidl\HasUploads\Tests\Traits;

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager;
use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Handlers\ModelUpdateHandler;
use christopheraseidl\HasUploads\Jobs\Contracts\Builder;
use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploads;
use christopheraseidl\HasUploads\Jobs\Contracts\MoveUploads;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Facades\Bus;

/**
 * A trait providing re-usable methods for tests in
 * /tests/Handlers/ModelUpdateHandler.
 */
trait ModelUpdateHandlerAssertions
{
    public function setHandler(): void
    {
        $this->handler = Reflect::on(new ModelUpdateHandler(
            app(UploadService::class),
            app(Builder::class),
            app(BatchManager::class),
            app(ModelFileChangeTracker::class)
        ));
    }

    public function assertJobsBatched(
        int $expectedDeleteCount = 1,
        int $expectedMoveCount = 1
    ): void {
        Bus::assertBatched(function ($batch) use ($expectedDeleteCount, $expectedMoveCount) {
            $batchCount = count($batch->jobs);

            $deleteJobsCount = $batch->jobs->filter(
                fn ($job) => $job instanceof DeleteUploads
            )->count();

            $moveJobsCount = $batch->jobs->filter(
                fn ($job) => $job instanceof MoveUploads
            )->count();

            return $batchCount === 4
                && $deleteJobsCount === $expectedDeleteCount
                && $moveJobsCount === $expectedMoveCount;
        });
    }
}
