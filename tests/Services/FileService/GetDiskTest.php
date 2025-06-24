<?php

namespace christopheraseidl\ModelFiler\Tests\Services\FileService;

/**
 * Tests FileService getDisk method.
 *
 * @covers \christopheraseidl\ModelFiler\Tests\Jobs\Services\FileService
 */
it('gets the disk', function () {
    $test_value = 'test_disk';
    config()->set('model-filer.disk', $test_value);

    expect($this->service->getDisk())->toBe($test_value);
});
