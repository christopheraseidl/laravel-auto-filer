<?php

namespace christopheraseidl\ModelFiler\Tests\Traits;

use christopheraseidl\ModelFiler\Jobs\Contracts\FileDeleter;
use christopheraseidl\ModelFiler\Traits\HasFileDeleter;

/**
 * Tests HasFileDeleter trait behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Traits\HasFileDeleter
 */
class HasFileDeleterTestClass
{
    use HasFileDeleter;

    public function __construct()
    {
        $this->deleter = \Mockery::mock(FileDeleter::class);
    }
}

it('returns the correct file deleter instance', function () {
    $mockDeleter = \Mockery::mock(FileDeleter::class);

    $class = new HasFileDeleterTestClass;

    // Assert the getter returns the correct instance
    expect($class->getDeleter())->toEqual($mockDeleter);
});
