<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService;

/**
 * Tests UploadService structure.
 *
 * @covers \christopheraseidl\HasUploads\Tests\Jobs\Services\UploadService
 */
it('implements the UploadService interface', function () {
    expect($this->reflection->implementsInterface('christopheraseidl\HasUploads\Contracts\UploadService'))
        ->toBeTrue();
});

it('uses the AttemptsFileMoves trait', function () {
    $traits = $this->reflection->getTraitNames();

    expect($traits)->toHaveCount(1)
        ->and($traits[0])->toBe('christopheraseidl\HasUploads\Traits\AttemptsFileMoves');
});
