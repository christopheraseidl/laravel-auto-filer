<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

use Illuminate\Support\Facades\Storage;

/**
 * Tests FileMover storage methods behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
it('can dynamically execute Laravel Storage methods', function (string $method, array $args) {
    Storage::shouldReceive('disk')
        ->with('disk')
        ->andReturnSelf();

    Storage::shouldReceive($method)
        ->once()
        ->with(...$args)
        ->andReturnTrue();

    $this->mover->shouldReceive('validateStorageResult')
        ->once();

    $this->mover->doStorage('disk', $method, $args);
})->with([
    'copy' => ['copy', ['disk', 'old/path/to/file.jpg', 'new/path/to/file.jpg']],
    'delete' => ['delete', ['disk', 'old/path/to/file.jpg']],
    'exists' => ['exists', ['disk', 'new/path/to/file.jpg']],
]);

it('can copy a file', function () {
    $this->mover->shouldReceive('doStorage')
        ->once()
        ->with($this->disk, 'copy', [$this->oldPath, $this->newPath]);

    $this->mover->copyFile($this->disk, $this->oldPath, $this->newPath);
});

it('can delete a file', function () {
    $this->mover->shouldReceive('doStorage')
        ->once()
        ->with($this->disk, 'delete', [$this->oldPath]);

    $this->mover->deleteFile($this->disk, $this->oldPath);
});

it('returns true when file exists and has content', function () {
    Storage::shouldReceive('disk')
        ->twice()
        ->with($this->disk)
        ->andReturnSelf();

    Storage::shouldReceive('exists')
        ->once()
        ->with($this->oldPath)
        ->andReturnTrue();

    Storage::shouldReceive('size')
        ->once()
        ->with($this->oldPath)
        ->andReturn(100);

    $result = $this->mover->fileExists($this->disk, $this->oldPath);

    expect($result)->toBeTrue();
});
