<?php

namespace christopheraseidl\HasUploads\Tests\Traits;

use christopheraseidl\HasUploads\Traits\HasPath;

class HasPathTestClass {
    use HasPath;

    public string $path;
}

it('returns the correct path', function () {
    $value = 'test_path';
    $class = new HasPathTestClass;
    $class->path = $value;

    expect($class->getPath())->toBe($value);
});
