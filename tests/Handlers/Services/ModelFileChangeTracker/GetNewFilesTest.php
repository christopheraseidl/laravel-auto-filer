<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\Services\ModelFileChangeTracker;

use christopheraseidl\HasUploads\Handlers\Services\ModelFileChangeTracker;

/**
 * Tests behavior of ModelFileChangeTracker's getNewFiles() method.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\Services\ModelFileChangeTracker
 */
beforeEach(function () {
    $this->tracker = new ModelFileChangeTracker;
});

it('returns the correct new files', function () {
    $this->model->fill([
        'string' => $this->newString,
        'array' => $this->newArray,
    ]);

    $newStringFiles = $this->tracker->getNewFiles($this->model, 'string');
    $newArrayFiles = $this->tracker->getNewFiles($this->model, 'array');

    expect($newStringFiles)->toBeArray()
        ->and($newStringFiles[0])->toBe('new-image.png')
        ->and($newArrayFiles)->toBeArray()
        ->and($newArrayFiles[0])->toBe('new-doc.txt');
});

it('returns an empty array if no files are being added', function () {
    $newStringFiles = $this->tracker->getNewFiles($this->model, 'string');
    $newArrayFiles = $this->tracker->getNewFiles($this->model, 'array');

    expect($newStringFiles)->toBeArray()
        ->and($newStringFiles)->toBeEmpty()
        ->and($newArrayFiles)->toBeArray()
        ->and($newArrayFiles)->toBeEmpty();
});

it('returns a type error if $model is null', function () {
    $files = $this->tracker->getNewFiles(null, 'string');
})->throws(\TypeError::class, 'Argument #1 ($model) must be of type Illuminate\Database\Eloquent\Model, null given');

it('returns a type error if $attribute is null', function () {
    $files = $this->tracker->getNewFiles($this->model, null);
})->throws(\TypeError::class, 'Argument #2 ($attribute) must be of type string, null given');

it('returns an empty array if $attribute does not exist on $model', function () {
    $files = $this->tracker->getNewFiles($this->model, 'nonexistent');

    expect($files)->toBeArray()
        ->and($files)->toBeEmpty();
});
