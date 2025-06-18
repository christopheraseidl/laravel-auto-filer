<?php

namespace christopheraseidl\HasUploads\Tests\HasUploads;

use christopheraseidl\HasUploads\HasUploads;
use christopheraseidl\HasUploads\Support\Contracts\UploadableAttributesBuilder;
use christopheraseidl\Reflect\Reflect;

it('returns the UploadableAttributesBuilder uploadable method', function () {
    $class = Reflect::on(new class
    {
        use HasUploads;
    });

    $result = $class->uploadable('avatar');

    expect($result)->toBeInstanceOf(UploadableAttributesBuilder::class);
});
