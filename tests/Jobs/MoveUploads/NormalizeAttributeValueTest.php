<?php

/**
 * Tests the MoveUploads normalizeAttributeValue method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\MoveUploads
 */
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

    $result = $this->mover->normalizeAttributeValue($this->model, $this->string);

    expect($result)->toBe($originalAttribute);
});

it('gracefully handles a null attribute', function () {
    $result = $this->mover->normalizeAttributeValue($this->model, $this->string);

    // No value has been assigned to $this->model->string, so the result is null
    expect($result)->toBeNull();
});

it('converts a model attribute currently set to a single-element array but cast as a string from an array to a string', function () {
    $originalAttribute = 'my_file.txt';
    $this->model->{$this->string} = [$originalAttribute];

    $result = $this->mover->normalizeAttributeValue($this->model, $this->string);

    expect($result)->toBe($originalAttribute);
});

it('returns a model attribute cast as an array without modification', function () {
    $originalAttribute = [
        'my_image1.png',
        'my_image2.png',
        'my_image3.png',
    ];
    $this->model->{$this->array} = $originalAttribute;
    $this->model->saveQuietly();

    $result = $this->mover->normalizeAttributeValue($this->model, $this->array);

    expect($result)->toBe($originalAttribute);
});

it('throws an exception when a model attribute cast as a string is saved as an array and has more than 1 element', function () {
    $originalAttribute = [
        'my_image1.png',
        'my_image2.png',
        'my_image3.png',
    ];

    $this->model->{$this->string} = $originalAttribute;

    expect(fn () => $this->mover->normalizeAttributeValue($this->model, $this->string))
        ->toThrow(\Exception::class, 'The attribute is being treated as an array but is not cast as an array in the model.');
});
