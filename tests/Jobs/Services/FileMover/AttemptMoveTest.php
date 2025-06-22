<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

use christopheraseidl\ModelFiler\Jobs\Services\FileMover;
use christopheraseidl\ModelFiler\Tests\TestTraits\FileMoverHelpers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

uses(FileMoverHelpers::class);

/**
 * Tests FileMover attemptMove method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
 */
beforeEach(function () {
    $name = 'test.txt';
    $this->oldPath = "uploads/{$name}";
    $this->newDir = 'new/dir';
    $this->newPath = "{$this->newDir}/{$name}";

    Storage::disk($this->disk)->put($this->oldPath, 'content');
});

it('moves a file and returns the new path', function () {
    $this->shouldValidateMover();

    $this->mover->shouldReceive('processMove')->andReturn($this->newPath);

    $result = $this->mover->attemptMove($this->disk, $this->oldPath, $this->newDir);

    expect($result)->toBe($this->newPath);
});

it('logs an error and throws an exception when move processing fails', function () {
    $this->shouldValidateMover();

    $this->mover->shouldReceive('processMove')
        ->andThrow(\Exception::class, 'Move processing failed');

    Log::shouldReceive('error')->once();

    expect(fn () => $this->mover->attemptMove($this->disk, $this->oldPath, $this->newDir))
        ->toThrow(\Exception::class);
});

it('throws exception when maxAttempts is 0', function () {
    expect(fn () => $this->mover->attemptMove($this->disk, $this->oldPath, $this->newDir, 0))
        ->toThrow(\Exception::class, 'maxAttempts must be at least 1.');
});
