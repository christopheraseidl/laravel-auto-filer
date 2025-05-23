<?php

namespace christopheraseidl\HasUploads\Tests\Payloads\Payload;

use christopheraseidl\HasUploads\Tests\TestClasses\Payload\TestPayload;

beforeEach(function () {
    $this->payload = new TestPayload('test value');
});

test('shouldBroadcastIndividualEvents() returns true', function () {
    expect($this->payload->shouldBroadcastIndividualEvents())
        ->toBeTrue();
});

test('the getKey() method returns the expected value', function () {
    expect($this->payload->getKey())->toBe('test_payload_key');
});

test('the getDisk() method returns the expected value', function () {
    expect($this->payload->getDisk())->toBe('test_payload_disk');
});
