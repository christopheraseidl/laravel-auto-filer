<?php

namespace christopheraseidl\AutoFiler\Tests\Helpers;

use Illuminate\Support\Facades\Storage;

/**
 * Quickly creates a partial mock of Storage disk for tests.
 */
trait UsesDiskPartialMock
{
    public \Mockery\MockInterface $mockDisk;

    /**
     * Create a partial mock of Storage disk.
     */
    public function partialMockDisk(string $disk = 'public'): void
    {
        $this->mockDisk = \Mockery::mock(Storage::disk($disk))->makePartial();
        Storage::set($disk, $this->mockDisk);
    }
}
