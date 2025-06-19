<?php

use christopheraseidl\ModelFiler\Events\FileOperationCompleted;
use christopheraseidl\ModelFiler\Events\FileOperationFailed;
use christopheraseidl\ModelFiler\Tests\TestClasses\Payload\TestPayloadNoConstructor;
use christopheraseidl\ModelFiler\Tests\TestClasses\TestJob;
use Illuminate\Support\Facades\Event;

/**
 * Tests the Job handleJob method.
 *
 * @covers \christopheraseidl\ModelFiler\Jobs\Job
 */
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
