<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\Services;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;

/**
 * Tests BatchManager handleFailure() behavior.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\Services\BatchManager
 */
it('broadcasts FileOperationFailed with correct data', function () {
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

it('passes exception to FileOperationFailed event', function () {
    $error = $this->error;

    $this->batchManager->handleFailure($this->batch, $this->model, $this->disk, $error);

    Event::assertDispatched(FileOperationFailed::class, function ($event) use ($error) {
        return $event->exception === $error;
    });
});

it('throws a TypeError with null model ID', function () {
    $model = $this->mock(Model::class, function (MockInterface $mock) {
        $mock->shouldReceive('getAttribute')->with('id')->andReturn(null);
    });

    $this->batchManager->handleFailure($this->batch, $model, $this->disk, $this->error);
})->throws(\TypeError::class, 'Argument #2 ($modelId) must be of type int, null given');

it('throws a TypeError with null disk', function () {
    $this->batchManager->handleFailure($this->batch, $this->model, null, $this->error);
})->throws(\TypeError::class, 'Argument #3 ($disk) must be of type string, null given');
