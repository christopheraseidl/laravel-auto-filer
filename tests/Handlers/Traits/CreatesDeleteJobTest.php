<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\Traits;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Handlers\Traits\CreatesDeleteJob;
use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploads;
use christopheraseidl\HasUploads\Jobs\Services\Builder;
use christopheraseidl\HasUploads\Jobs\Validators\BuilderValidator;
use christopheraseidl\HasUploads\Tests\TestModels\TestModel;
use christopheraseidl\Reflect\Reflect;

class CreatesDeleteJobClass
{
    use CreatesDeleteJob;
}

/**
 * Tests CreatesDeleteJob behavior.
 *
 * @covers christopheraseidl\HasUploads\Handlers\Traits\CreatesDeleteJob
 */
beforeEach(function () {
    $this->class = Reflect::on(new CreatesDeleteJobClass);
    $this->builder = new Builder(new BuilderValidator);
    $this->attribute = 'string';
    $this->type = 'images';
});

it('returns a DeleteJob with the correct data', function () {
    $removedFiles = ['file1.jpg', 'file2.png'];

    $job = $this->class->createDeleteJob(
        $this->builder,
        $this->model,
        $this->attribute,
        $this->type,
        $this->disk,
        $removedFiles
    );

    $payload = Reflect::on($job)->payload;
    $payload = Reflect::on($payload);

    expect($job)->toBeInstanceOf(DeleteUploads::class)
        ->and($payload->modelClass)->toBe(TestModel::class)
        ->and($payload->modelId)->toBe(1)
        ->and($payload->modelAttribute)->toBe('string')
        ->and($payload->modelAttributeType)->toBe('images')
        ->and($payload->operationType)->toBe(OperationType::Delete)
        ->and($payload->operationScope)->toBe(OperationScope::File)
        ->and($payload->disk)->toBe($this->disk)
        ->and($payload->filePaths)->toBe($removedFiles)
        ->and($payload->newDir)->toBeNull();
});

it('returns null if $removedFiles is empty', function () {
    $removedFiles = [];

    $job = $this->class->createDeleteJob(
        $this->builder,
        $this->model,
        $this->attribute,
        $this->type,
        $this->disk,
        $removedFiles
    );

    expect($job)->toBeNull();
});

it('throws a TypeError with null $builder', function () {
    $removedFiles = ['file1.jpg', 'file2.png'];

    $job = $this->class->createDeleteJob(
        null,
        $this->model,
        $this->attribute,
        $this->type,
        $this->disk,
        $removedFiles
    );
})->throws(\TypeError::class, 'Argument #1 ($builder) must be of type christopheraseidl\HasUploads\Jobs\Contracts\Builder, null given');

it('throws a TypeError with null $model', function () {
    $removedFiles = ['file1.jpg', 'file2.png'];

    $job = $this->class->createDeleteJob(
        $this->builder,
        null,
        $this->attribute,
        $this->type,
        $this->disk,
        $removedFiles
    );
})->throws(\TypeError::class, 'Argument #2 ($model) must be of type Illuminate\Database\Eloquent\Model, null given');

it('throws a TypeError with null $attribute', function () {
    $removedFiles = ['file1.jpg', 'file2.png'];

    $job = $this->class->createDeleteJob(
        $this->builder,
        $this->model,
        null,
        $this->type,
        $this->disk,
        $removedFiles
    );
})->throws(\TypeError::class, 'Argument #3 ($attribute) must be of type string, null given');

it('throws a TypeError with null $disk', function () {
    $removedFiles = ['file1.jpg', 'file2.png'];

    $job = $this->class->createDeleteJob(
        $this->builder,
        $this->model,
        $this->attribute,
        $this->type,
        null,
        $removedFiles
    );
})->throws(\TypeError::class, 'Argument #5 ($disk) must be of type string, null given');

it('throws a TypeError with null $removedFiles', function () {
    $removedFiles = ['file1.jpg', 'file2.png'];

    $job = $this->class->createDeleteJob(
        $this->builder,
        $this->model,
        $this->attribute,
        $this->type,
        $this->disk,
        null
    );
})->throws(\TypeError::class, 'Argument #6 ($removedFiles) must be of type array, null given');
