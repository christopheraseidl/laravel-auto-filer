<?php

namespace christopheraseidl\ModelFiler\Tests\Traits;

use christopheraseidl\ModelFiler\Traits\HasPath;

/**
 * Tests HasPath and behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Traits\HasPath
 */
class HasPathTestClass
{
    use HasPath;

    public string $path;
}

it('returns the correct path', function () {
    $value = 'test_path';
    $class = new HasPathTestClass;
    $class->path = $value;

    expect($class->getPath())->toBe($value);
});
