<?php

namespace christopheraseidl\HasUploads\Tests\Traits;

use christopheraseidl\HasUploads\Traits\AttemptsFileMoves;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttemptsFileMovesTest
{
    use AttemptsFileMoves;
}

beforeEach(function () {
    $this->trait = new AttemptsFileMovesTest;
});

it('moves a file and returns the new path', function () {
    $name = 'test.txt';
    $upload = UploadedFile::fake()->create($name, 100);
    $oldPath = "uploads/{$name}";
    $newDir = 'new/dir';
    $newPath = "{$newDir}/{$name}";

    Storage::disk($this->disk)->put($oldPath, $upload);

    expect(Storage::disk($this->disk)->exists($oldPath))->toBeTrue();

    $result = $this->trait->attemptMove($this->disk, $oldPath, $newDir);

    expect(Storage::disk($this->disk)->exists($oldPath))->toBeFalse()
        ->and(Storage::disk($this->disk)->exists($newPath))->toBeTrue()
        ->and($result)->toBe($newPath);
});

it('succeeds after 1-2 failures', function (int $maxFailures) {
    $diskMock = \Mockery::mock();
    $count = 0;

    $diskMock->shouldReceive('move')
        ->times($maxFailures + 1)
        ->andReturnUsing(function () use (&$count, $maxFailures) {
            $count++;
            if ($count <= $maxFailures) {
                throw new \Exception('Move failed');
            }

            return null; // success on 3rd call
        });

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->times($maxFailures + 1)
        ->andReturn($diskMock);

    $result = $this->trait->attemptMove($this->disk, 'old/path/to/file.txt', 'new/dir');

    expect($result)->toBe('new/dir/file.txt');
})->with([
    [1],
    [2],
]);

it('throws an exception after 3 errors', function () {
    $diskMock = \Mockery::mock();

    $diskMock->shouldReceive('move')
        ->times(3)
        ->andThrow(new \Exception('Move failed'));

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->times(3)
        ->andReturn($diskMock);

    expect(fn () => $this->trait->attemptMove($this->disk, 'old/path/to/file.txt', 'new/dir'))
        ->toThrow(\Exception::class);
});

it('throws final exception when maxAttempts is 0', function () {
    expect(fn () => $this->trait->attemptMove($this->disk, 'old/file.txt', 'new', 0))
        ->toThrow(\Exception::class, 'Failed to move file after 0 attempts.');
});
