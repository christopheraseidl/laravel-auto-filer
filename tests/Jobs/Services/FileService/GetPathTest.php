<?php

namespace christopheraseidl\ModelFiler\Tests\Jobs\Services\FileService;

/**
 * Tests FileService getPath method.
 *
 * @covers \christopheraseidl\ModelFiler\Tests\Jobs\Services\FileService
 */
it('gets the upload path', function () {
    $test_value = 'test/path';
    config()->set('model-filer.path', $test_value);

    expect($this->service->getPath())->toBe($test_value);
});
