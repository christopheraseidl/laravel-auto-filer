<?php

use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();
});

test('handleJob() broadcasts completion event on success when broadcasting enabled', function () {
    $this->job->handleJob(function () {});

    Event::assertDispatched(FileOperationCompleted::class);
});

test('handleJob() broadcasts failure event on failure when broadcasting enabled', function () {
    $this->job->handleJob(function () {
        throw new \Exception('Job failure.');
    });

    Event::assertDispatched(FileOperationFailed::class);
});
