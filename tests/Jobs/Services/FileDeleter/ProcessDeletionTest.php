<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileDeleter;

/**
 * Tests FileDeleter processDeletion method behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Services\FileDeleter
 */
it('deletes a file and returns true', function () {
    $this->breaker->shouldReceive('canAttempt')->andReturnTrue();

    $this->deleter->shouldReceive('performDeletion')->once()->andReturnTrue();

    $result = $this->deleter->processDeletion($this->disk, $this->path, $this->maxAttempts);

    expect($result)->toBeTrue();
});

it('succeeds after 1-2 failures when maxAttempts is 3', function (int $failures) {
    $count = 0;

    $this->breaker->shouldReceive('canAttempt')->andReturnTrue();

    $this->deleter->shouldReceive('performDeletion')
        ->times($failures + 1)
        ->andReturnUsing(function () use (&$count, $failures) {
            $count++;
            if ($count <= $failures) {
                throw new \Exception('Deletion failed.');
            }

            return true;
        });

    $this->deleter->shouldReceive('handleProcessDeletionException')->times($failures);
    $this->deleter->shouldReceive('handleDeletionFailure')->never();

    $result = $this->deleter->processDeletion($this->disk, $this->path, $this->maxAttempts);

    expect($result)->toBeTrue();
})->with([
    1,
    2,
]);

it('handles deletion failure when maximum attempts reached', function () {
    $this->breaker->shouldReceive('canAttempt')
        ->times($this->maxAttempts)
        ->andReturnTrue();

    $this->deleter->shouldReceive('performDeletion')
        ->times($this->maxAttempts)
        ->andThrow(\Exception::class, 'Deletion failed');

    $this->deleter->shouldReceive('handleProcessDeletionException')
        ->times($this->maxAttempts);

    $this->deleter->shouldReceive('handleDeletionFailure')
        ->once()
        ->with($this->disk, $this->path, $this->maxAttempts, 'Deletion failed');

    $result = $this->deleter->processDeletion($this->disk, $this->path, $this->maxAttempts);

    expect($result)->toBeFalse();
});

it('handles deletion failure when circuit breaker blocks attempt', function () {
    $this->breaker->shouldReceive('canAttempt')->once()->andReturnFalse();

    $this->deleter->shouldReceive('handleDeletionFailure')->once();

    $result = $this->deleter->processDeletion($this->disk, $this->path, $this->maxAttempts);

    expect($result)->toBeFalse();
});
