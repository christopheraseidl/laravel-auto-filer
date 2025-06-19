<?php

namespace christopheraseidl\ModelFiler\Tests\TestTraits;

use christopheraseidl\ModelFiler\Handlers\Contracts\BatchManager;
use christopheraseidl\ModelFiler\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\ModelFiler\Handlers\ModelUpdateHandler;
use christopheraseidl\ModelFiler\Jobs\Contracts\Builder;
use christopheraseidl\ModelFiler\Jobs\Contracts\DeleteUploads;
use christopheraseidl\ModelFiler\Jobs\Contracts\MoveUploads;
use christopheraseidl\ModelFiler\Services\Contracts\FileService;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Facades\Bus;

/**
 * A trait providing re-usable methods for tests in /tests/Handlers/ModelUpdateHandler.
 */
trait ModelUpdateHandlerHelpers
{
    public function setHandler(): void
    {
        $this->handler = Reflect::on(new ModelUpdateHandler(
            app(FileService::class),
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
            $batchJobCount = count($batch->jobs);

            $deleteJobsCount = $batch->jobs->filter(
                fn ($job) => $job instanceof DeleteUploads
            )->count();

            $moveJobsCount = $batch->jobs->filter(
                fn ($job) => $job instanceof MoveUploads
            )->count();

            return $batchJobCount === 4 // Based on setup in /tests/Handlers/ModelUpdateHandler and Pest.php
                && $deleteJobsCount === $expectedDeleteCount
                && $moveJobsCount === $expectedMoveCount;
        });
    }
}
