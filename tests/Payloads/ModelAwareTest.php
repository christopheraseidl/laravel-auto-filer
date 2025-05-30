<?php

namespace christopheraseidl\HasUploads\Tests\Payloads;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Payloads\Contracts\ModelAware as ModelAwareContract;
use christopheraseidl\HasUploads\Payloads\ModelAware;

/**
 * Tests ModelAware structure and behavior.
 *
 * @covers \christopheraseidl\HasUploads\Payloads\ModelAware
 */
class TestModelAware extends ModelAware
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return true;
    }
}

beforeEach(function () {
    $this->class = $this->model::class;
    $this->payload = new TestModelAware(
        $this->class,
        1,
        'string',
        'images',
        OperationType::Move,
        OperationScope::File,
        'test_disk',
    );
});

it('implements the ModelAware contract', function () {
    expect($this->payload)->toBeInstanceOf(ModelAwareContract::class);
});

test('the getKey method returns the expected value', function () {
    $fileIdentifier = md5(serialize(null));

    expect($this->payload->getKey())->toBe(
        OperationType::Move->value
        .'_'.OperationScope::File->value
        .'_'.$this->model::class
        .'_'.$this->model->id
        .'_'.$fileIdentifier
    );
});

test('the toArray method returns the expected array', function () {
    $array = [
        'modelClass' => $this->class,
        'modelId' => $this->model->id,
        'modelAttribute' => 'string',
        'modelAttributeType' => 'images',
        'operationType' => OperationType::Move,
        'operationScope' => OperationScope::File,
        'disk' => 'test_disk',
        'filePaths' => null,
        'newDir' => null,
    ];

    expect($this->payload->toArray())->toBe($array);
});

test('the resolveModel returns the model', function () {
    expect($this->payload->resolveModel()->is($this->model))->toBeTrue();
});

test('the getModelClass method returns model class', function () {
    expect($this->payload->getModelClass())->toBe($this->class);
});

test('the getModelId method returns model ID', function () {
    expect($this->payload->getModelId())->toBe($this->model->id);
});

test('the getModelAttribute method returns model attribute', function () {
    expect($this->payload->getModelAttribute())->toBe('string');
});

test('the getModelAttributeType method returns model attribute type', function () {
    expect($this->payload->getModelAttributeType())->toBe('images');
});

test('the getOperationType method returns the expected OperationType', function () {
    expect($this->payload->getOperationType())->toBe(OperationType::Move);
});

test('the getOperationScope method returns the expected OperationScope', function () {
    expect($this->payload->getOperationScope())->toBe(OperationScope::File);
});

test('the getFilePaths method returns the filePaths property', function () {
    expect($this->payload->getFilePaths())->toBeNull();
});

test('the getNewDir method returns the newDir property', function () {
    expect($this->payload->getNewDir())->toBeNull();
});
