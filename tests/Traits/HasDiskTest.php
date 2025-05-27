<?php

namespace christopheraseidl\HasUploads\Tests\Traits;

use christopheraseidl\HasUploads\Traits\HasDisk;

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
