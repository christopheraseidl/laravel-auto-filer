<?php

namespace christopheraseidl\HasUploads\Tests\Payloads\Payload;

use christopheraseidl\HasUploads\Payloads\Contracts\Payload as PayloadContract;
use christopheraseidl\HasUploads\Tests\TestClasses\Payload\TestPayload;

/**
 * Tests Payload class structure.
 *
 * @covers \christopheraseidl\HasUploads\Payloads\Payload
 */
beforeEach(function () {
    $this->payload = new TestPayload('test value');
});

it('implements the Payload contract', function () {
    expect($this->payload)->toBeInstanceOf(PayloadContract::class);
});
