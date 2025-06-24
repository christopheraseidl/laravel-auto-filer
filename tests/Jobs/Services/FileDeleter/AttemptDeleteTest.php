<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileDeleter;

use christopheraseidl\ModelFiler\Tests\TestTraits\FileDeleterHelpers;

uses(FileDeleterHelpers::class);

/**
 * Tests FileDeleter attemptDelete method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileDeleter
 */
it('deletes a file and returns true', function () {
    $this->shouldValidateDeleter();

    $this->deleter->shouldReceive('processDeletion')
        ->once()
        ->with($this->disk, $this->path, $this->maxAttempts)
        ->andReturnTrue();

    $result = $this->deleter->attemptDelete($this->disk, $this->path, $this->maxAttempts);

    expect($result)->toBeTrue();
});

it('throws exception when maxAttempts is 0', function () {
    expect(fn () => $this->deleter->attemptDelete($this->disk, $this->path, 0))
        ->toThrow(\Exception::class, 'maxAttempts must be at least 1.');
});

it('allows errors to bubble up from processDeletion', function () {
    $this->shouldValidateDeleter();

    $this->deleter->shouldReceive('processDeletion')
        ->once()
        ->with($this->disk, $this->path, $this->maxAttempts)
        ->andThrow(\Exception::class, 'Deletion failure');

    expect(fn () => $this->deleter->attemptDelete($this->disk, $this->path, $this->maxAttempts))
        ->toThrow(\Exception::class, 'Deletion failure');
});
