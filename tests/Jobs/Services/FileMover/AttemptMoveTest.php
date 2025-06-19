<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileMover;

use christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker;
use christopheraseidl\ModelFiler\Jobs\Services\FileMover;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Tests FileMover attemptMove method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileMover
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
    $diskMock = \Mockery::mock(Storage::disk($this->disk))->makePartial();

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->andReturn($diskMock);

    // Mock limited failures
    $diskMock->shouldReceive('copy')
        ->times($failures + 1)
        ->andReturnUsing(function () use (&$count, $failures) {
            $count++;
            if ($count <= $failures) {
                throw new \Exception('Copy failed.');
            }

            return true;
        });

    // Mock exists() for generateUniqueFileName - return false so no renaming needed
    $diskMock->shouldReceive('exists')
        ->with($this->newPath)
        ->andReturn(false)
        ->times($failures + 1);

    // Mock exists() and size() for validateCopiedFile - return true and >0 to pass validation
    $diskMock->shouldReceive('exists')
        ->with($this->newPath)
        ->andReturn(true)
        ->once();

    $diskMock->shouldReceive('size')
        ->with($this->newPath)
        ->andReturn(100);

    // Mock delete() of old copy of file
    $diskMock->shouldReceive('delete')
        ->with($this->oldPath)
        ->andReturn(true)
        ->once();

    $result = $this->mover->attemptMove($this->disk, $this->oldPath, $this->newDir);

    expect($result)->toBe($this->newPath);
})->with([
    1,
    2,
]);

it('calls attemptUndoMove when move fails and there are moved files', function () {
    $this->mover->movedFiles = ['old/file.txt' => 'new/file.txt'];

    $diskMock = \Mockery::mock(Storage::disk($this->disk))->makePartial();
    $diskMock->shouldReceive('copy')->andThrow(new \Exception('Copy failed.'));
    $diskMock->shouldReceive('exists')->andReturn(false); // For generateUniqueFileName

    Storage::shouldReceive('disk')->andReturn($diskMock);

    expect(fn () => $this->mover->attemptMove($this->disk, $this->oldPath, $this->newDir))
        ->toThrow(\Exception::class);

    // If attemptUndoMove fired and succeeded, movedFiles should be cleared
    expect($this->mover->movedFiles)->toBeEmpty();
});

it('logs an error and throws an exception after 3 errors when maxAttempts is 3', function () {
    $diskMock = \Mockery::mock();
    $diskMock->shouldReceive('copy')
        ->andThrow(new \Exception('Copy failed.'));

    $diskMock->shouldReceive('exists')
        ->with($this->newPath) // First call in generateUniqueFileName
        ->andReturn(false) // Return false so the loop exits immediately
        ->times(3);

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
