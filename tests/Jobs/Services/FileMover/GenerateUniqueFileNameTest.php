<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\FileMover;

use christopheraseidl\HasUploads\Jobs\Contracts\CircuitBreaker;
use christopheraseidl\HasUploads\Jobs\Services\FileMover;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

beforeEach(function () {
    $breaker = $this->mock(CircuitBreaker::class);
    $mover = new FileMover($breaker);
    $this->mover = Reflect::on($mover);
});

it('returns unique path without modification', function () {
    $mockDisk = $this->mock('disk', function (MockInterface $mock) {
        $mock->shouldReceive('exists')
            ->andReturnFalse();
    });

    Storage::shouldReceive('disk')
        ->andReturn($mockDisk);

    $path = 'path/to/file.jpg';

    $result = $this->mover->generateUniqueFileName($this->disk, $path);

    expect($result)->toBe($result);
});

it('returns correct path if other files exist', function (int $numberOfFilesWithSameName) {
    $diskMock = $this->mock('disk', function (MockInterface $mock) use ($numberOfFilesWithSameName) {
        $count = 0;

        $mock->shouldReceive('exists')
            ->andReturnUsing(function () use (&$count, $numberOfFilesWithSameName) {
                $count++;

                if ($count <= $numberOfFilesWithSameName) {
                    return true;
                }

                return false;
            });
    });

    Storage::shouldReceive('disk')
        ->andReturn($diskMock);

    $path = 'path/to/file.jpg';

    $result = $this->mover->generateUniqueFileName($this->disk, $path);

    expect($result)->toEndWith("file_{$numberOfFilesWithSameName}.jpg");
})->with([
    '1 file' => 1,
    '2 files' => 2,
    '3 files' => 3,
]);
