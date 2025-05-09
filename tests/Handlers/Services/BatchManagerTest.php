<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\Services;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use christopheraseidl\HasUploads\Handlers\Services\BatchManager;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

class TestJobOne {}

class TestJobTwo {}

/**
 * Tests BatchManager behavior.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\Services\BatchManager
 */
beforeEach(function () {
    Bus::fake();
    Event::fake();

    $this->batch = \Mockery::mock(Batch::class);

    $this->batchManager = new BatchManager;

    $this->model = \Mockery::mock(Model::class);
    $this->model->shouldReceive('getAttribute')->with('id')->andReturn(1);

    $this->disk = 'local';

    $this->error = new \Exception('Test error message.');
});

it('dispatches batch with the correct parameters', function (array $jobs) {
    $description = 'Test Batch';

    $this->batchManager->dispatch($jobs, $this->model, $this->disk, $description);

    Bus::assertBatched(function ($batch) use ($jobs, $description) {
        return $batch->name === $description
            && $batch->jobs->all() === collect($jobs)->all();
    });
})->with([
    'with jobs' => [
        [new TestJobOne, new TestJobTwo]
    ],
    'without jobs' => [
        []
    ],
]);

test('handleSuccess broadcasts FileOperationCompleted with correct data', function () {
    $this->batchManager->handleSuccess($this->batch, $this->model, $this->disk);

    Event::assertDispatched(FileOperationCompleted::class, function ($event) {
        $payload = Reflect::on($event->payload);
        $trimmedModelClass = preg_replace('/^Mockery_\d+_/', '', $payload->modelClass);

        return $trimmedModelClass === 'Illuminate_Database_Eloquent_Model'
            && $payload->modelId === 1
            && $payload->operationType === OperationType::Update
            && $payload->operationScope === OperationScope::Batch
            && $payload->disk === 'local'
            && $payload->modelAttribute === null
            && $payload->modelAttributeType === null
            && $payload->filePaths === null
            && $payload->newDir === null;
    });
});

test('handleSuccess throws a TypeError with null model ID', function () {
    $model = \Mockery::mock(Model::class);
    $model->shouldReceive('getAttribute')->with('id')->andReturn(null);

    $this->batchManager->handleSuccess($this->batch, $model, $this->disk);
})->throws(\TypeError::class, 'Argument #2 ($modelId) must be of type int, null given');

test('handleSuccess throws a TypeError with null disk', function () {
    $this->batchManager->handleSuccess($this->batch, $this->model, null);
})->throws(\TypeError::class, 'Argument #3 ($disk) must be of type string, null given');

test('handleFailure broadcasts FileOperationFailed with correct data', function () {
    $this->batchManager->handleFailure($this->batch, $this->model, $this->disk, $this->error);

    Event::assertDispatched(FileOperationFailed::class, function ($event) {
        $payload = Reflect::on($event->payload);
        $trimmedModelClass = preg_replace('/^Mockery_\d+_/', '', $payload->modelClass);

        return $trimmedModelClass === 'Illuminate_Database_Eloquent_Model'
            && $payload->modelId === 1
            && $payload->operationType === OperationType::Update
            && $payload->operationScope === OperationScope::Batch
            && $payload->disk === 'local'
            && $payload->modelAttribute === null
            && $payload->modelAttributeType === null
            && $payload->filePaths === null
            && $payload->newDir === null;
    });
});

test('handleFailure passes exception to FileOperationFailed event', function () {
    $error = $this->error;
    
    $this->batchManager->handleFailure($this->batch, $this->model, $this->disk, $error);

    Event::assertDispatched(FileOperationFailed::class, function ($event) use ($error) {
        return $event->exception === $error;
    });
});

test('handleFailure throws a TypeError with null model ID', function () {
    $model = \Mockery::mock(Model::class);
    $model->shouldReceive('getAttribute')->with('id')->andReturn(null);

    $this->batchManager->handleFailure($this->batch, $model, $this->disk, $this->error);
})->throws(\TypeError::class, 'Argument #2 ($modelId) must be of type int, null given');

test('handleFailure throws a TypeError with null disk', function () {
    $this->batchManager->handleFailure($this->batch, $this->model, null, $this->error);
})->throws(\TypeError::class, 'Argument #3 ($disk) must be of type string, null given');
