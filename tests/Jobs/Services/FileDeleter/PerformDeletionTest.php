<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileDeleter;

use Illuminate\Support\Facades\Storage;

it('performs deletion and returns the result', function () {
    $dir = 'path/to/delete';

    $this->deleter->shouldReceive('deleteDirectoryOrFile')
        ->once()
        ->with($this->disk, $dir)
        ->andReturnTrue();

    $this->deleter->shouldReceive('handleDeletionResult')
        ->once()
        ->with(true)
        ->andReturnTrue();

    $result = $this->deleter->performDeletion($this->disk, $dir);

    expect($result)->toBeTrue();
});

it('can delete a directory', function () {
    $dir = 'path/to/dir';

    Storage::disk($this->disk)->makeDirectory($dir);

    $result = $this->deleter->deleteDirectoryOrFile($this->disk, $dir);

    expect($result)->toBeTrue();
});

it('can delete a file', function () {
    $file = 'path/to/file.jpg';

    Storage::disk($this->disk)->put($file, 'contents');

    $result = $this->deleter->deleteDirectoryOrFile($this->disk, $file);

    expect($result)->toBeTrue();
});
