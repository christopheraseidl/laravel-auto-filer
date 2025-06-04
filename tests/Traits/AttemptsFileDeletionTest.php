<?php

namespace christopheraseidl\HasUploads\Tests\Traits;

use christopheraseidl\HasUploads\Traits\AttemptsFileDeletion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Tests AttemptsFileMoves attemptUndoMove method behavior.
 *
 * @covers \christopheraseidl\HasUploads\Traits\AttemptsFileMoves
 */
class AttemptsFileDeletionTest
{
    use AttemptsFileDeletion;
}

beforeEach(function () {
    $this->trait = new AttemptsFileDeletionTest;
    $this->path = 'uploads/file.txt';

    Storage::disk($this->disk)->put($this->path, 'test file content');
});

it('deletes a file and returns true', function () {
    $result = $this->trait->attemptDelete($this->disk, $this->path);

    expect($result)->toBeTrue();
});

it('succeeds after 1-2 failures when maxAttempts is 3', function (int $failures) {
    $count = 0;
    $diskMock = \Mockery::mock();

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->andReturn($diskMock);

    $diskMock->shouldReceive('delete')
        ->times($failures + 1)
        ->andReturnUsing(function () use (&$count, $failures) {
            $count++;
            if ($count <= $failures) {
                throw new \Exception('Deletion failed.');
            }

            return true;
        });

    $result = $this->trait->attemptDelete($this->disk, $this->path);

    expect($result)->toBeTrue();
})->with([
    1,
    2,
]);

it('logs an error and throws an exception after 3 errors when maxAttempts is 3', function () {
    $diskMock = \Mockery::mock();

    Log::spy();

    Storage::shouldReceive('disk')
        ->with($this->disk)
        ->andReturn($diskMock);

    $diskMock->shouldReceive('delete')
        ->andThrow(new \Exception('Deletion failed.'));

    expect(fn () => $this->trait->attemptDelete($this->disk, $this->path))
        ->toThrow(\Exception::class);

    Log::shouldHaveReceived('error')
        ->with('Failed to delete file after 3 attempts.', [
            'disk' => $this->disk,
            'path' => $this->path,
            'lastError' => 'Deletion failed.',
        ]);
});

it('throws exception when maxAttempts is 0', function () {
    expect(fn () => $this->trait->attemptDelete($this->disk, $this->path, 0))
        ->toThrow(\Exception::class, 'maxAttempts must be at least 1.');
});
