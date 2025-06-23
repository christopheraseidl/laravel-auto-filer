<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

/**
 * Tests FileMover generateUniqueFileName method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('returns unique path without modification', function () {
    $path = 'path/to/file.jpg';

    $result = $this->mover->generateUniqueFileName($this->disk, $path);

    expect($result)->toBe($result);
});

it('returns correct path if other files exist', function (int $numberOfFilesWithSameName) {
    $count = 0;

    $this->mover->shouldReceive('fileExists')
        ->times($numberOfFilesWithSameName + 1)
        ->andReturnUsing(function () use (&$count, $numberOfFilesWithSameName) {
            $count++;

            if ($count <= $numberOfFilesWithSameName) {
                return true;
            }

            return false;
        });

    $path = 'path/to/file.jpg';

    $result = $this->mover->generateUniqueFileName($this->disk, $path);

    expect($result)->toEndWith("file_{$numberOfFilesWithSameName}.jpg");
})->with([
    '1 file' => 1,
    '2 files' => 2,
    '3 files' => 3,
]);
