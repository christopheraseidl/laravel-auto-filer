<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\Traits;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Handlers\Traits\CreatesMoveJob;
use christopheraseidl\HasUploads\Jobs\Contracts\MoveUploads;
use christopheraseidl\HasUploads\Jobs\Services\Builder;
use christopheraseidl\HasUploads\Jobs\Validators\BuilderValidator;
use christopheraseidl\Reflect\Reflect;

class CreatesMoveJobClass {
    use CreatesMoveJob;
}

/**
 * Tests CreatesMoveJob behavior.
 *
 * @covers christopheraseidl\HasUploads\Handlers\Traits\CreatesMoveJob
 */
beforeEach(function () {
    $this->class = Reflect::on(new CreatesMoveJobClass);
    $this->builder = new Builder(new BuilderValidator);
    $this->attribute = 'string';
    $this->type = 'images';
});

it('returns a DeleteJob with the correct data', function () {
    $newFiles = ['file1.jpg', 'file2.png'];

    $job = $this->class->createMoveJob(
        $this->builder,
        $this->model,
        $this->attribute,
        $this->type,
        $this->disk,
        $newFiles
    );

    $payload = Reflect::on($job)->payload;
    $payload = Reflect::on($payload);

    expect($job)->toBeInstanceOf(MoveUploads::class)
        ->and($payload->modelClass)->toBe('christopheraseidl\HasUploads\Tests\TestModels\TestModel')
        ->and($payload->modelId)->toBe(1)
        ->and($payload->modelAttribute)->toBe('string')
        ->and($payload->modelAttributeType)->toBe('images')
        ->and($payload->operationType)->toBe(OperationType::Move)
        ->and($payload->operationScope)->toBe(OperationScope::File)
        ->and($payload->disk)->toBe($this->disk)
        ->and($payload->filePaths)->toBe($newFiles)
        ->and($payload->newDir)->toBe('test_models/1/images');
});

it('returns null if $newFiles is empty', function () {
    $newFiles = [];

    $job = $this->class->createMoveJob(
        $this->builder,
        $this->model,
        $this->attribute,
        $this->type,
        $this->disk,
        $newFiles
    );

    expect($job)->toBeNull();
});

it('throws a TypeError with null $builder', function () {
    $newFiles = ['file1.jpg', 'file2.png'];

    $job = $this->class->createMoveJob(
        null,
        $this->model,
        $this->attribute,
        $this->type,
        $this->disk,
        $newFiles
    );
})->throws(\TypeError::class, 'Argument #1 ($builder) must be of type christopheraseidl\HasUploads\Jobs\Contracts\Builder, null given');

it('throws a TypeError with null $model', function () {
    $newFiles = ['file1.jpg', 'file2.png'];

    $job = $this->class->createMoveJob(
        $this->builder,
        null,
        $this->attribute,
        $this->type,
        $this->disk,
        $newFiles
    );
})->throws(\TypeError::class, 'Argument #2 ($model) must be of type Illuminate\Database\Eloquent\Model, null given');

it('throws a TypeError with null $attribute', function () {
    $newFiles = ['file1.jpg', 'file2.png'];

    $job = $this->class->createMoveJob(
        $this->builder,
        $this->model,
        null,
        $this->type,
        $this->disk,
        $newFiles
    );
})->throws(\TypeError::class, 'Argument #3 ($attribute) must be of type string, null given');

it('throws a TypeError with null $disk', function () {
    $newFiles = ['file1.jpg', 'file2.png'];

    $job = $this->class->createMoveJob(
        $this->builder,
        $this->model,
        $this->attribute,
        $this->type,
        null,
        $newFiles
    );
})->throws(\TypeError::class, 'Argument #5 ($disk) must be of type string, null given');

it('throws a TypeError with null $newFiles', function () {
    $newFiles = ['file1.jpg', 'file2.png'];

    $job = $this->class->createMoveJob(
        $this->builder,
        $this->model,
        $this->attribute,
        $this->type,
        $this->disk,
        null
    );
})->throws(\TypeError::class, 'Argument #6 ($newFiles) must be of type array, null given');
