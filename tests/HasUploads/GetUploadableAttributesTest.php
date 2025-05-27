<?php

namespace christopheraseidl\HasUploads\Tests\HasUploads;

use christopheraseidl\HasUploads\HasUploads;

class GetUploadableAttributesTestClass
{
    use HasUploads;
}

it('returns an empty array by default', function () {
    $class = new GetUploadableAttributesTestClass;
    $attributes = $class->getUploadableAttributes();

    expect($attributes)->toBeEmpty();
});

it('retrieves the uploadable attributes when used by a model', function () {
    $attributes = $this->model->getUploadableAttributes();

    expect($attributes['string'])->toBe('images')
        ->and($attributes['array'])->toBe('documents');
});
