<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\FileMover;

use christopheraseidl\HasUploads\Jobs\Services\CircuitBreaker;
use christopheraseidl\HasUploads\Jobs\Services\FileMover;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Tests FileMover attemptMove method behavior.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\Services\FileMover
 */
beforeEach(function () {
    $mover = new FileMover(
        new CircuitBreaker('test-breaker')
    );
    $this->mover = Reflect::on($mover);

    $name = 'test.txt';
    $this->oldPath = "uploads/{$name}";
    $this->newDir = 'new/dir';
    $this->newPath = "{$this->newDir}/{$name}";

    Storage::disk($this->disk)->put($this->oldPath, 'test file content');
});

it('moves a file and returns the new path', function () {
    expect(Storage::disk($this->disk)->exists($this->oldPath))->toBeTrue();

    $result = $this->mover->attemptMove($this->disk, $this->oldPath, $this->newDir);

    expect(Storage::disk($this->disk)->exists($this->oldPath))->toBeFalse()
        ->and(Storage::disk($this->disk)->exists($this->newPath))->toBeTrue()
        ->and($result)->toBe($this->newPath);
});

it('succeeds after 1-2 failures when maxAttempts is 3', function (int $failures) {
    $count = 0;
    $diskMock = \Mockery::mock();

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->andReturn($diskMock);

    $diskMock->shouldReceive('copy')
        ->times($failures + 1)
        ->andReturnUsing(function () use (&$count, $failures) {
            $count++;
            if ($count <= $failures) {
                throw new \Exception('Copy failed.');
            }

            return true;
        });

    // Mock file operations.
    $diskMock->shouldReceive('exists')->andReturn(true);
    $diskMock->shouldReceive('size')->andReturn(100);
    $diskMock->shouldReceive('delete')->andReturn(true);

    $result = $this->mover->attemptMove($this->disk, $this->oldPath, $this->newDir);

    expect($result)->toBe($this->newPath);
})->with([
    1,
    2,
]);

it('calls attemptUndoMove when move fails and there are moved files', function () {
    $this->mover->movedFiles = [
        $this->oldPath => $this->newPath,
    ];

    $diskMock = \Mockery::mock();

    Storage::shouldReceive('disk')->with($this->disk)->andReturn($diskMock);

    // Mock the failed copy attempt.
    $diskMock->shouldReceive('copy')->times(3)->andThrow(new \Exception('Copy failed.'));

    // Mock the undo operation.
    $diskMock->shouldReceive('exists')->andReturn(true);
    $diskMock->shouldReceive('size')->andReturn(100);
    $diskMock->shouldReceive('delete')->andReturn(true);

    expect(fn () => $this->mover->attemptMove($this->disk, $this->oldPath, $this->newDir))
        ->toThrow(\Exception::class);
});

it('logs an error and throws an exception after 3 errors when maxAttempts is 3', function () {
    $diskMock = \Mockery::mock();
    $diskMock->shouldReceive('copy')
        ->andThrow(new \Exception('Copy failed.'));

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->andReturn($diskMock);

    Log::spy();

    expect(fn () => $this->mover->attemptMove($this->disk, $this->oldPath, $this->newDir))
        ->toThrow(\Exception::class);

    Log::shouldHaveReceived('error')
        ->with('Failed to move file after 3 attempts.', [
            'disk' => $this->disk,
            'old_path' => $this->oldPath,
            'new_dir' => $this->newDir,
            'max_attempts' => 3,
            'last_error' => 'Copy failed.',
        ]);
});

it('throws exception when maxAttempts is 0', function () {
    expect(fn () => $this->mover->attemptMove($this->disk, $this->oldPath, $this->newDir, 0))
        ->toThrow(\Exception::class, 'maxAttempts must be at least 1.');
});
