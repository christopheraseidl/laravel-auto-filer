<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService;

/**
 * Tests UploadService getPath method.
 *
 * @covers \christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService
 */
it('gets the upload path', function () {
    $test_value = 'test/path';
    config()->set('has-uploads.path', $test_value);

    expect($this->service->getPath())->toBe($test_value);
});
