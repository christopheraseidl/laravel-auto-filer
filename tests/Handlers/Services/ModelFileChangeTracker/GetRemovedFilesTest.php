<?php

namespace christopheraseidl\ModelFiler\Tests\Handlers\Services\ModelFileChangeTracker;

use christopheraseidl\ModelFiler\Handlers\Services\ModelFileChangeTracker;

/**
 * Tests behavior of ModelFileChangeTracker's getRemovedFiles method.
 *
 * @covers \christopheraseidl\ModelFiler\Handlers\Services\ModelFileChangeTracker
 */
beforeEach(function () {
    $this->tracker = new ModelFileChangeTracker;
});

it('returns the correct files marked for deletion', function () {
    $this->model->fill([
        'string' => $this->newString,
        'array' => $this->newArray,
    ]);

    $removedStringFiles = $this->tracker->getRemovedFiles($this->model, 'string');
    $removedArrayFiles = $this->tracker->getRemovedFiles($this->model, 'array');

    expect($removedStringFiles)->toBeArray();
    expect($removedStringFiles[0])->toBe('image.jpg');
    expect($removedArrayFiles)->toBeArray();
    expect($removedArrayFiles[0])->toBe('document1.doc');
});

it('returns an empty array if no files are marked for deletion', function () {
    $removedStringFiles = $this->tracker->getRemovedFiles($this->model, 'string');
    $removedArrayFiles = $this->tracker->getRemovedFiles($this->model, 'array');

    expect($removedStringFiles)->toBeArray()
        ->and($removedStringFiles)->toBeEmpty();
    expect($removedArrayFiles)->toBeArray()
        ->and($removedArrayFiles)->toBeEmpty();
});

it('returns a type error if $model is null', function () {
    $files = $this->tracker->getRemovedFiles(null, 'string');
})->throws(\TypeError::class, 'Argument #1 ($model) must be of type Illuminate\Database\Eloquent\Model, null given');

it('returns a type error if $attribute is null', function () {
    $files = $this->tracker->getRemovedFiles($this->model, null);
})->throws(\TypeError::class, 'Argument #2 ($attribute) must be of type string, null given');

it('returns an empty array if $attribute does not exist on $model', function () {
    $files = $this->tracker->getRemovedFiles($this->model, 'nonexistent');

    expect($files)->toBeArray()
        ->and($files)->toBeEmpty();
});
