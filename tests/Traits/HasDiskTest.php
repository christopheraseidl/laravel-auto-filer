<?php

namespace christopheraseidl\ModelFiler\Tests\Traits;

use christopheraseidl\ModelFiler\Traits\HasDisk;

/**
 * Tests HasDisk and behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Traits\HasDisk
 */
class HasDiskTestClass
{
    use HasDisk;

    public string $disk;
}

it('returns the correct disk', function () {
    $value = 'test_disk';
    $class = new HasDiskTestClass;
    $class->disk = $value;

    expect($class->getDisk())->toBe($value);
});
