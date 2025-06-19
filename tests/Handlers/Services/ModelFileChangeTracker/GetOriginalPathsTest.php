<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\Services\ModelFileChangeTracker;

use christopheraseidl\HasUploads\Handlers\Services\ModelFileChangeTracker;

/**
 * Tests behavior of ModelFileChangeTracker's getOriginalPaths() method.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\Services\ModelFileChangeTracker
 */
beforeEach(function () {
    $this->tracker = new ModelFileChangeTracker;
});

it('returns the correct values for a changed model attribute', function () {
    $originalString = $this->model->string;
    $originalArray = $this->model->array;

    $this->model->fill([
        'string' => $this->newString,
        'array' => $this->newArray,
    ]);

    $stringPaths = $this->tracker->getOriginalPaths($this->model, 'string');
    $arrayPaths = $this->tracker->getOriginalPaths($this->model, 'array');

    expect($stringPaths)->toBeArray();
    expect($stringPaths[0])->toBe($originalString);
    expect($arrayPaths)->toBeArray()
        ->and($arrayPaths)->toBe($originalArray);
});

it('returns a value equal to the current path if nothing has been changed', function () {
    $stringPaths = $this->tracker->getOriginalPaths($this->model, 'string');
    $arrayPaths = $this->tracker->getOriginalPaths($this->model, 'array');

    expect($stringPaths)->toBeArray();
    expect($stringPaths[0])->toBe($this->model->string);
    expect($arrayPaths)->toBeArray()
        ->and($arrayPaths)->toBe($this->model->array);
});

it('returns a type error if $model is null', function () {
    $files = $this->tracker->getOriginalPaths(null, 'string');
})->throws(\TypeError::class, 'Argument #1 ($model) must be of type Illuminate\Database\Eloquent\Model, null given');

it('returns a type error if $attribute is null', function () {
    $files = $this->tracker->getOriginalPaths($this->model, null);
})->throws(\TypeError::class, 'Argument #2 ($attribute) must be of type string, null given');

it('returns an empty array if $attribute does not exist on $model', function () {
    $files = $this->tracker->getOriginalPaths($this->model, 'nonexistent');

    expect($files)->toBeArray()
        ->and($files)->toBeEmpty();
});
