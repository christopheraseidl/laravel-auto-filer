<?php

namespace christopheraseidl\ModelFiler\Tests\Handlers\Services;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Events\FileOperationCompleted;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;

/**
 * Tests BatchManager handleSuccess method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Handlers\Services\BatchManager
 */
it('broadcasts FileOperationCompleted with correct data', function () {
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

it('throws a TypeError with null model ID', function () {
    $model = $this->mock(Model::class, function (MockInterface $mock) {
        $mock->shouldReceive('getAttribute')->with('id')->andReturn(null);
    });

    $this->batchManager->handleSuccess($this->batch, $model, $this->disk);
})->throws(\TypeError::class, 'Argument #2 ($modelId) must be of type int, null given');

it('throws a TypeError with null disk', function () {
    $this->batchManager->handleSuccess($this->batch, $this->model, null);
})->throws(\TypeError::class, 'Argument #3 ($disk) must be of type string, null given');
