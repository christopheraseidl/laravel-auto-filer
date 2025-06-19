<?php

namespace christopheraseidl\ModelFiler\Tests\Events;

use Illuminate\Broadcasting\PrivateChannel;

/**
 * Tests structure and behavior of all child events.
 *
 * @covers \christopheraseidl\ModelFiler\Events\BatchFileOperationCompleted
 * @covers \christopheraseidl\ModelFiler\Events\BatchFileOperationFailed
 * @covers \christopheraseidl\ModelFiler\Events\FileOperationCompleted
 * @covers \christopheraseidl\ModelFiler\Events\FileOperationFailed
 * @covers \christopheraseidl\ModelFiler\Events\FileOperationStarted
 */
it('returns the expected payload and structure', function (string $event) {
    $event = "christopheraseidl\\ModelFiler\\Events\\$event";
    $event = new $event($this->payload);

    expect($event->payload)->toBe($this->payload);
    expect($event->payload->getKey())->toBe('test_payload_key');
    expect($event->payload->getDisk())->toBe('test_payload_disk');
    expect($event->payload->toArray())->toBe([
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
    $event = "christopheraseidl\\ModelFiler\\Events\\$event";
    $event = new $event($this->payload, $exception);

    expect($event->exception)->toBe($exception);
    expect($exception->getMessage())->toBe('Test exception');
})->with([
    'BatchFileOperationFailed',
    'FileOperationFailed',
]);

it('broadcasts on the correct channel', function (string $event) {
    $event = "christopheraseidl\\ModelFiler\\Events\\$event";
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
