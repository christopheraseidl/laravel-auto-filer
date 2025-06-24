<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\DeleteUploads;

use christopheraseidl\ModelFiler\Jobs\Contracts\FileDeleter;

/**
 * Tests the DeleteUploads handle method with array (multiple file) attributes.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\DeleteUploads
 */
it('deletes arrays of files', function (array $files) {
    $count = count($files);

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn($files);

    $deleterService = $this->mock(FileDeleter::class);
    $deleterService->shouldReceive('attemptDelete')
        ->times($count)
        ->withArgs(function ($disk, $file) use ($files) {
            return $disk === $this->disk && in_array($file, $files);
        })
        ->andReturnTrue();

    $this->deleter->shouldReceive('getDeleter')
        ->times($count)
        ->andReturn($deleterService);

    $this->deleter->executeDeletion();
})->with([
    'multiple_files' => [
        [
            'path/to/image.jpg',
            'path/to/document.txt',
            'path/to/error.log',
            'path/to/avagar.png',
        ],
    ],
    'single_file' => [
        ['just/one/file.txt'],
    ],
]);

it('deletes only the provided files and handles a sparse array gracefully', function () {
    $files = [
        'path/to/image.jpg',
        'path/to/document.txt',
        'path/to/error.log',
        'path/to/avagar.png',
    ];
    unset($files[0]);
    unset($files[2]);
    $count = count($files);

    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn($files);

    $deleterService = $this->mock(FileDeleter::class);
    $deleterService->shouldReceive('attemptDelete')
        ->times($count)
        ->withArgs(function ($disk, $file) use ($files) {
            return $disk === $this->disk && in_array($file, $files);
        })
        ->andReturnTrue();

    $this->deleter->shouldReceive('getDeleter')
        ->times($count)
        ->andReturn($deleterService);

    $this->deleter->executeDeletion();
});

it('handles null array attribute gracefully', function () {
    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturnNull();

    $this->deleter->shouldReceive('getDeleter')->never();

    $this->deleter->executeDeletion();
});

it('handles empty array attribute gracefully', function () {
    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturnNull();

    $this->deleter->shouldReceive('getDeleter')->never();

    $this->deleter->executeDeletion();
});

it('handles empty path attribute gracefully', function () {
    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn(['']);

    $deleterService = $this->mock(FileDeleter::class);
    $deleterService->shouldReceive('attemptDelete')
        ->once()
        ->with($this->disk, '')
        ->andReturnTrue();

    $this->deleter->shouldReceive('getDeleter')
        ->once()
        ->andReturn($deleterService);

    $this->deleter->executeDeletion();
});

it('throws an exception when path attribute is null', function () {
    $this->payload->shouldReceive('getFilePaths')
        ->once()
        ->andReturn([null]);

    $deleterService = $this->mock(FileDeleter::class);
    $deleterService->shouldReceive('attemptDelete')
        ->never();

    $this->deleter->shouldReceive('getDeleter')
        ->once()
        ->andReturn($deleterService);

    expect(fn () => $this->deleter->executeDeletion())
        ->toThrow(\TypeError::class, 'Argument #2 ($path) must be of type string, null given');
});
