<?php

namespace christopheraseidl\HasUploads\Tests\Traits\AttemptsFileMoves;

use christopheraseidl\HasUploads\Traits\AttemptsFileMoves;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Facades\Storage;

/**
 * Tests AttemptsFileMoves attemptMove method behavior.
 *
 * @covers \christopheraseidl\HasUploads\Traits\AttemptsFileMoves
 */
class AttemptMoveTest
{
    use AttemptsFileMoves;
}

beforeEach(function () {
    $this->trait = Reflect::on(new AttemptMoveTest);

    $name = 'test.txt';
    $this->oldPath = "uploads/{$name}";
    $this->newDir = 'new/dir';
    $this->newPath = "{$this->newDir}/{$name}";

    Storage::disk($this->disk)->put($this->oldPath, 'test file content');
});

it('moves a file and returns the new path', function () {
    expect(Storage::disk($this->disk)->exists($this->oldPath))->toBeTrue();

    $result = $this->trait->attemptMove($this->disk, $this->oldPath, $this->newDir);

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

    $diskMock->shouldReceive('exists')->andReturn(true);
    $diskMock->shouldReceive('size')->andReturn(100);
    $diskMock->shouldReceive('delete')->andReturn(true);

    $result = $this->trait->attemptMove($this->disk, $this->oldPath, $this->newDir);

    expect($result)->toBe($this->newPath);
})->with([
    1,
    2,
]);

it('calls attemptUndoMove when move fails and there are moved files', function () {
    $this->trait->movedFiles = [
        $this->oldPath => $this->newPath,
    ];

    $diskMock = \Mockery::mock();

    Storage::shouldReceive('disk')->with($this->disk)->andReturn($diskMock);

    // Mock the failed copy attempt
    $diskMock->shouldReceive('copy')->times(3)->andThrow(new \Exception('Copy failed.'));

    // Mock the undo operation.
    $diskMock->shouldReceive('exists')->andReturn(true);
    $diskMock->shouldReceive('size')->andReturn(100);
    $diskMock->shouldReceive('delete')->andReturn(true);

    expect(fn () => $this->trait->attemptMove($this->disk, $this->oldPath, $this->newDir))
        ->toThrow(\Exception::class);
});

it('throws an exception after 3 errors when maxAttempts is 3', function () {
    $diskMock = \Mockery::mock();

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->andReturn($diskMock);

    $diskMock->shouldReceive('copy')
        ->andThrow(new \Exception('Copy failed.'));

    expect(fn () => $this->trait->attemptMove($this->disk, $this->oldPath, $this->newDir))
        ->toThrow(\Exception::class);
});

it('throws final exception when maxAttempts is 0', function () {
    expect(fn () => $this->trait->attemptMove($this->disk, $this->oldPath, $this->newDir, 0))
        ->toThrow(\Exception::class, 'maxAttempts must be at least 1.');
});
