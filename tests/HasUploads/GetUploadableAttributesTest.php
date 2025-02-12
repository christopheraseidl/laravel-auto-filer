<?php

namespace christopheraseidl\HasUploads\Tests\HasUploads;

use christopheraseidl\Reflect\Reflect;

it('retrieves the uploadable attributes', function () {
    $attributes = Reflect::on($this->model)->getUploadableAttributes();

    expect($attributes['string'])->toBe('images')
        ->and($attributes['array'])->toBe('documents');
});
