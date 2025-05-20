<?php

namespace christopheraseidl\HasUploads\Tests\Events;

use Illuminate\Broadcasting\PrivateChannel;

/**
 * Tests structure and behavior of all child events.
 *
 * @covers \christopheraseidl\HasUploads\Events
 */
it('returns the expected payload and structure', function (string $event) {
    $event = "christopheraseidl\\HasUploads\\Events\\$event";
    $event = new $event($this->payload);

    expect($event->payload)->toBe($this->payload)
        ->and($event->payload->getKey())->toBe('test_payload_key')
        ->and($event->payload->getDisk())->toBe('test_payload_disk')
        ->and($event->payload->toArray())->toBe([
            'key' => 'value',
        ]);
})->with([
    'BatchFileOperationCompleted',
    'BatchFileOperationFailed',
    'FileOperationCompleted',
    'FileOperationFailed',
    'FileOperationStarted',
]);

test('failure events return the expected exception', function (string $event) {
    $exception = new \Exception('Test exception');
    $event = "christopheraseidl\\HasUploads\\Events\\$event";
    $event = new $event($this->payload, $exception);

    expect($event->exception)->toBe($exception)
        ->and($exception->getMessage())->toBe('Test exception');
})->with([
    'BatchFileOperationFailed',
    'FileOperationFailed',
]);

it('broadcasts on the correct channel', function (string $event) {
    $event = "christopheraseidl\\HasUploads\\Events\\$event";
    $event = new $event($this->payload);

    expect($event->broadcastOn())->toEqual(
        [
            new PrivateChannel('test_channel'),
        ]
    );
})->with([
    'BatchFileOperationCompleted',
    'BatchFileOperationFailed',
    'FileOperationCompleted',
    'FileOperationFailed',
    'FileOperationStarted',
]);
