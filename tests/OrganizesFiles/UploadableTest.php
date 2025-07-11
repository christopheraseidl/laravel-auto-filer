<?php

namespace christopheraseidl\ModelFiler\Tests\ModelFiler;

use christopheraseidl\ModelFiler\HasFiles;
use christopheraseidl\ModelFiler\Support\Contracts\UploadableAttributesBuilder;
use christopheraseidl\Reflect\Reflect;

it('returns the UploadableAttributesBuilder uploadable method', function () {
    $class = Reflect::on(new class
    {
        use HasFiles;
    });

    $result = $class->uploadable('avatar');

    expect($result)->toBeInstanceOf(UploadableAttributesBuilder::class);
});
