<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService;

/**
 * Tests UploadService structure.
 *
 * @covers \christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService
 */
it('implements the UploadService interface', function () {
    expect($this->reflection->implementsInterface('christopheraseidl\HasUploads\Services\Contracts\UploadService'))
        ->toBeTrue();
});
