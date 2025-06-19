<?php

namespace christopheraseidl\ModelFiler\Tests\ModelFiler;

use christopheraseidl\ModelFiler\HasFiles;

class GetUploadableAttributesTestClass
{
    use HasFiles;
}

it('returns an empty array by default', function () {
    $class = new GetUploadableAttributesTestClass;
    $attributes = $class->getUploadableAttributes();

    expect($attributes)->toBeEmpty();
});

it('retrieves the uploadable attributes when used by a model', function () {
    $attributes = $this->model->getUploadableAttributes();

    expect($attributes['string'])->toBe('images');
    expect($attributes['array'])->toBe('documents');
});
