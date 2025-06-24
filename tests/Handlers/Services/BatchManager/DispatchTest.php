<?php

namespace christopheraseidl\ModelFiler\Tests\Handlers\Services;

use Illuminate\Support\Facades\Bus;

class DispatchTestJobOne {}

class DispatchTestJobTwo {}

/**
 * Tests BatchManager dispatch behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Handlers\Services\BatchManager
 */
it('dispatches batch with the correct parameters', function (array $jobs) {
    $description = 'Test Batch';

    $this->batchManager->dispatch($jobs, $this->model, $this->disk, $description);

    Bus::assertBatched(function ($batch) use ($jobs, $description) {
        return $batch->name === $description
            && $batch->jobs->all() === collect($jobs)->all();
    });
})->with([
    'with jobs' => [
        [new DispatchTestJobOne, new DispatchTestJobTwo],
    ],
    'without jobs' => [
        [],
    ],
]);
