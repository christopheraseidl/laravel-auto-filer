<?php

use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use christopheraseidl\HasUploads\Tests\TestClasses\Payload\TestPayloadNoConstructor;
use christopheraseidl\HasUploads\Tests\TestClasses\TestJob;
use Illuminate\Support\Facades\Event;

class HandleJobTestBraodcastFalsePayload extends TestPayloadNoConstructor
{
    public function shouldBroadcastIndividualEvents(): bool
    {
        return false;
    }
}

beforeEach(function () {
    Event::fake();

    $this->jobWithouBroadcast = new TestJob(new HandleJobTestBraodcastFalsePayload);
});

it('broadcasts completion event on success when broadcasting enabled', function () {
    $this->job->handleJob(function () {});

    Event::assertDispatched(FileOperationCompleted::class);
});

it('does not broadcast completion event on success when broadcasting disabled', function () {
    $this->jobWithouBroadcast->handleJob(function () {});

    Event::assertNotDispatched(FileOperationCompleted::class);
});

it('broadcasts failure event on failure when broadcasting enabled', function () {
    $this->job->handleJob(function () {
        throw new \Exception('Job failure.');
    });

    Event::assertDispatched(FileOperationFailed::class);
});

it('does not broadcast failure event on success when broadcasting disabled', function () {
    $this->jobWithouBroadcast->handleJob(function () {
        throw new \Exception('Job failure.');
    });

    Event::assertNotDispatched(FileOperationFailed::class);
});
