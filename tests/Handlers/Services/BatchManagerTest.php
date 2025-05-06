<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\Services;

use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Handlers\Services\BatchManager;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

class TestJobOne {}

class TestJobTwo {}

beforeEach(function () {
    $this->batchManager = new BatchManager();
});


it('dispatches batch with the correct parameters', function (array $jobs) {
    Bus::fake();
    $description = 'Test Batch';

    $this->batchManager->dispatch($jobs, $this->model, $this->disk, $description);

    Bus::assertBatched(function ($batch) use ($jobs, $description) {
        return $batch->name === $description
            && $batch->jobs->all() === collect($jobs)->all();
    });
})->with([
    [[]],
    [[new TestJobOne, new TestJobTwo]]
]);

it('correctly handles a successful batch', function () {
    Event::fake();
    $batch = \Mockery::mock(Batch::class);
    
    // Create a real model or a mock that has the necessary methods
    $model = \Mockery::mock(Model::class);
    $model->shouldReceive('getAttribute')->with('id')->andReturn(1);
    
    // For class_basename to work
    $modelClass = 'TestModel';
    $model->shouldReceive('__toString')->andReturn($modelClass);
    
    $disk = 'local';
    
    // Remove the dd() call from your BatchManager::handleSuccess method first
    // or modify it temporarily for the test
    
    // For the test to work, we need to ensure BatchUpdate::make gets all required parameters
    $this->batchManager->handleSuccess($batch, $model, $disk);
    
    // Verify broadcast was called with appropriate payload
    Event::assertDispatched(FileOperationCompleted::class);
});

/*it('correctly handles a failed batch', function () {

});*/
