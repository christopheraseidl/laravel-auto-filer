<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\CleanOrphanedUploads;

use christopheraseidl\HasUploads\Contracts\CleanerContract;
use christopheraseidl\HasUploads\Events\CleanupCompleted;
use christopheraseidl\HasUploads\Events\CleanupFailed;
use christopheraseidl\HasUploads\Jobs\CleanOrphanedUploads;
use ErrorException;
use Illuminate\Support\Facades\Event;
use Mockery;

/**
 * Tests the CleanOrphanedUploads job's handle method, including error
 * conditions.
 *
 * @covers \christopheraseidl\HasUploads\Jobs\CleanOrphanedUploads
 */
beforeEach(function () {
    config()->set('has-uploads.path', 'uploads');

    Event::fake([
        CleanupCompleted::class,
        CleanupFailed::class,
    ]);
});

it('calls the clean method and broadcasts the job complete event', function () {
    $mockCleaner = Mockery::mock(CleanerContract::class)
        ->shouldReceive('clean')
        ->once()
        ->andReturn(null)
        ->getMock();

    $job = new CleanOrphanedUploads($mockCleaner);

    $job->handle();

    Event::assertDispatched(CleanupCompleted::class);
});

it('broadcasts failure event when clean method fails', function () {
    $mockCleaner = Mockery::mock(CleanerContract::class)
        ->shouldReceive('clean')
        ->once()
        ->andThrow(ErrorException::class)
        ->getMock();

    $job = new CleanOrphanedUploads($mockCleaner);

    $job->handle();

    Event::assertDispatched(CleanupFailed::class);
});
