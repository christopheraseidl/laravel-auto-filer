<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\Services\ModelFileChangeTracker;

use christopheraseidl\HasUploads\Handlers\Services\ModelFileChangeTracker;

/**
 * Tests behavior of ModelFileChangeTracker's getCurrentPaths() method.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\Services\ModelFileChangeTracker
 */
beforeEach(function () {
    $this->tracker = new ModelFileChangeTracker;
});

it('returns the correct values for a changed model attribute', function () {
    $this->model->fill([
        'string' => $this->newString,
        'array' => $this->newArray,
    ]);

    $currentString = $this->model->string;
    $currentArray = $this->model->array;

    $stringPaths = $this->tracker->getCurrentPaths($this->model, 'string');
    $arrayPaths = $this->tracker->getCurrentPaths($this->model, 'array');

    expect($stringPaths)->toBeArray()
        ->and($stringPaths[0])->toBe($currentString)
        ->and($arrayPaths)->toBeArray()
        ->and($arrayPaths)->toBe($currentArray);
});

it('returns the correct values if nothing has been changed', function () {
    $stringPaths = $this->tracker->getCurrentPaths($this->model, 'string');
    $arrayPaths = $this->tracker->getCurrentPaths($this->model, 'array');

    expect($stringPaths)->toBeArray()
        ->and($stringPaths[0])->toBe($this->model->string)
        ->and($arrayPaths)->toBeArray()
        ->and($arrayPaths)->toBe($this->model->array);
});

it('returns a type error if $model is null', function () {
    $files = $this->tracker->getCurrentPaths(null, 'string');
})->throws(\TypeError::class, 'Argument #1 ($model) must be of type Illuminate\Database\Eloquent\Model, null given');

it('returns a type error if $attribute is null', function () {
    $files = $this->tracker->getCurrentPaths($this->model, null);
})->throws(\TypeError::class, 'Argument #2 ($attribute) must be of type string, null given');

it('returns an empty array if $attribute does not exist on $model', function () {
    $files = $this->tracker->getCurrentPaths($this->model, 'nonexistent');

    expect($files)->toBeArray()
        ->and($files)->toBeEmpty();
});
