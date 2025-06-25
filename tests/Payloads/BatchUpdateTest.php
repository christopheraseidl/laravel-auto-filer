<?php

namespace christopheraseidl\ModelFiler\Tests\Payloads;

use christopheraseidl\ModelFiler\Payloads\BatchUpdate;

/**
 * Tests BatchUpdate structure and behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Payloads\BatchUpdate
 */
it('can broadcast events', function () {
    $payload = $this->partialMock(BatchUpdate::class);

    expect($payload->shouldBroadcastIndividualEvents())->toBeTrue();
});
