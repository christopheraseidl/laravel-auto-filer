<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\FileMover;

use christopheraseidl\HasUploads\Jobs\Services\CircuitBreaker;
use christopheraseidl\HasUploads\Jobs\Services\FileMover;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Tests FileMover attemptUndoMove method behavior.
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
    $this->mover->movedFiles = [
        $this->oldPath => $this->newPath,
    ];

    Storage::disk($this->disk)->put($this->newPath, 'test file content');
});

it('it undoes moving a file and returns the original path', function () {
    expect(Storage::disk($this->disk)->exists($this->newPath))->toBeTrue();

    $result = $this->mover->attemptUndoMove($this->disk);

    expect(Storage::disk($this->disk)->exists($this->newPath))->toBeFalse()
        ->and(Storage::disk($this->disk)->exists($this->oldPath))->toBeTrue()
        ->and($result)->toBe([$this->oldPath => $this->newPath]);
});

it('succeeds after 1-2 failures when maxAttempts is 3', function (int $failures) {
    $failureCount = 0;
    $oldPathCheckCount = 0;
    $diskMock = \Mockery::mock();

    Storage::shouldReceive('disk')
        ->andReturn($diskMock);

    $diskMock->shouldReceive('exists')
        ->with($this->newPath)
        ->times($failures + 1)
        ->andReturnUsing(function () use (&$failureCount, $failures) {
            $failureCount++;
            if ($failureCount <= $failures) {
                throw new \Exception('Existence check failed.');
            }

            return true;
        });

    $diskMock->shouldReceive('exists')
        ->with($this->oldPath)
        ->andReturnUsing(function () use (&$oldPathCheckCount) {
            $oldPathCheckCount++;

            return $oldPathCheckCount >= 2;
        });

    $diskMock->shouldReceive('size')
        ->andReturn(100);

    $diskMock->shouldReceive('copy')
        ->andReturn(true);

    $diskMock->shouldReceive('delete')
        ->andReturn(true);

    $result = $this->mover->attemptUndoMove($this->disk);

    expect($result)->toBe([$this->oldPath => $this->newPath]);
})->with([
    1,
    2,
]);

it('throws exception on partial undo failure and calls uncommitMovedFile', function () {
    $secondOldPath = 'second/path/test.pdf';
    $secondNewPath = 'second/new/path/test.pdf';
    $this->mover->movedFiles = [
        $this->oldPath => $this->newPath,
        $secondOldPath => $secondNewPath,
    ];

    $diskMock = \Mockery::mock();
    Storage::shouldReceive('disk')->andReturn($diskMock);

    $count = 0;

    $diskMock->shouldReceive('exists')
        ->andReturnUsing(function ($path) use (&$count) {
            if (str_contains($path, '.txt')) {
                return true;
            }

            throw new \Exception('Undo failed');
        });

    $diskMock->shouldReceive('size')->andReturn(100);
    $diskMock->shouldReceive('copy')->andReturn(true);
    $diskMock->shouldReceive('delete')->andReturn(true);

    expect(fn () => $this->mover->attemptUndoMove($this->disk))
        ->toThrow(\Exception::class, 'Failed to undo 1 file move(s)')
        ->and($this->mover->movedFiles)->not->toContain($this->newPath)
        ->and($this->mover->movedFiles)->toContain($secondNewPath);
});

it('logs an error and throws an exception after 3 errors when maxAttempts is 3', function () {
    Log::spy();

    $diskMock = \Mockery::mock();

    Storage::shouldReceive('disk')
        ->andReturn($diskMock);

    $diskMock->shouldReceive('exists')
        ->andThrow(new \Exception('Existence check failed.'));

    expect(fn () => $this->mover->attemptUndoMove($this->disk))
        ->toThrow(\Exception::class);

    Log::shouldHaveReceived('error')
        ->with('File move undo failure.', [
            'disk' => $this->disk,
            'failed' => [$this->oldPath => $this->newPath],
            'succeeded' => [],
        ]);
});

it('throws an invalid argument exception when maxAttempts is less than 1', function () {
    expect(fn () => $this->mover->attemptUndoMove($this->disk, 0))
        ->toThrow(\InvalidArgumentException::class, 'maxAttempts must be at least 1.');
});
