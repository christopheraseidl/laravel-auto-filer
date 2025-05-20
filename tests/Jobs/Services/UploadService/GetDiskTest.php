<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService;

/**
 * Tests UploadService getDisk() method.
 *
 * @covers \christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService
 */
it('gets the disk', function () {
    $test_value = 'test_disk';
    config()->set('has-uploads.disk', $test_value);

    expect($this->service->getDisk())->toBe($test_value);
});
