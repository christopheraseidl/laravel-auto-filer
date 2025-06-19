<?php

/**
 * Tests the MoveUploads normalizeAttributeValue method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\MoveUploads
 */

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Jobs\MoveUploads;
use christopheraseidl\ModelFiler\Payloads\MoveUploads as MoveUploadsPayload;
use christopheraseidl\ModelFiler\Tests\TestModels\TestModel;
use christopheraseidl\Reflect\Reflect;

beforeEach(function () {
    $this->string = 'string';
    $this->stringType = 'images';
    $this->array = 'array';
    $this->arrayType = 'documents';
    $this->newDir = 'test_models/1/images';
});

it('returns a string without modification', function () {
    $originalAttribute = 'my_file.txt';
    $this->model->{$this->string} = $originalAttribute;
    $this->model->saveQuietly();

    $payload = new MoveUploadsPayload(
        TestModel::class,
        1,
        $this->string,
        $this->stringType,
        OperationType::Move,
        OperationScope::File,
        $this->disk,
        [$originalAttribute],
        $this->newDir
    );

    $job = Reflect::on(new MoveUploads($payload));

    $attribute = $job->normalizeAttributeValue($this->model, $this->string);

    expect($attribute)->toBe($originalAttribute);
});

it('gracefully handles a null attribute', function () {
    $originalAttribute = 'my_file.txt';

    $payload = new MoveUploadsPayload(
        TestModel::class,
        1,
        $this->string,
        $this->stringType,
        OperationType::Move,
        OperationScope::File,
        $this->disk,
        [$originalAttribute],
        $this->newDir
    );

    $job = Reflect::on(new MoveUploads($payload));

    $attribute = $job->normalizeAttributeValue($this->model, $this->string);

    expect($attribute)->toBeNull();
});

it('converts a model attribute currently set to a single-element array but cast as a string from an array to a string', function () {
    $originalAttribute = 'my_file.txt';
    $this->model->{$this->string} = [$originalAttribute];

    $payload = new MoveUploadsPayload(
        TestModel::class,
        1,
        $this->string,
        $this->stringType,
        OperationType::Move,
        OperationScope::File,
        $this->disk,
        [$originalAttribute],
        $this->newDir
    );

    $job = Reflect::on(new MoveUploads($payload));

    $attribute = $job->normalizeAttributeValue($this->model, $this->string);

    expect($attribute)->toBe($originalAttribute);
});

it('returns a model attribute cast as an array without modification', function () {
    $originalAttribute = [
        'my_image1.png',
        'my_image2.png',
        'my_image3.png',
    ];
    $this->model->{$this->array} = $originalAttribute;
    $this->model->saveQuietly();

    $payload = new MoveUploadsPayload(
        TestModel::class,
        1,
        $this->array,
        $this->arrayType,
        OperationType::Move,
        OperationScope::File,
        $this->disk,
        $originalAttribute,
        $this->newDir
    );

    $job = Reflect::on(new MoveUploads($payload));

    $attribute = $job->normalizeAttributeValue($this->model, $this->array);

    expect($attribute)->toBe($originalAttribute);
});

it('throws an exception when a model attribute cast as a string is saved as an array and has more than 1 element', function () {
    $originalAttribute = [
        'my_image1.png',
        'my_image2.png',
        'my_image3.png',
    ];

    $this->model->{$this->string} = $originalAttribute;

    $payload = new MoveUploadsPayload(
        TestModel::class,
        1,
        $this->string,
        $this->stringType,
        OperationType::Move,
        OperationScope::File,
        $this->disk,
        $originalAttribute,
        $this->newDir
    );

    $job = Reflect::on(new MoveUploads($payload));

    expect(fn () => $job->normalizeAttributeValue($this->model, $this->string))
        ->toThrow(\Exception::class, 'The attribute is being treated as an array but is not cast as an array in the model.');
});
