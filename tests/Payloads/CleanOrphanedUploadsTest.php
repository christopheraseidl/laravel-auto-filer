<?php

namespace christopheraseidl\ModelFiler\Tests\Payloads;

use christopheraseidl\ModelFiler\Payloads\CleanOrphanedUploads;
use christopheraseidl\ModelFiler\Payloads\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsContract;

/**
 * Tests CleanOrphanedUploads structure and behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Payloads\CleanOrphanedUploads
 */
beforeEach(function () {
    $this->payload = new CleanOrphanedUploads(
        'test_disk',
        'test_path',
        12
    );
});

it('implements the CleanOrphanedUploads contract', function () {
    expect($this->payload)->toBeInstanceOf(CleanOrphanedUploadsContract::class);
});

test('the getKey method returns the expected value', function () {
    expect($this->payload->getKey())->toBe('clean_orphaned_uploads');
});

test('the shouldBroadcastIndividualEvents method returns true', function () {
    expect($this->payload->shouldBroadcastIndividualEvents())->toBeTrue();
});

test('the toArray method returns the expected array', function () {
    $array = [
        'disk' => 'test_disk',
        'path' => 'test_path',
        'cleanup_threshold_hours' => 12,
    ];

    expect($this->payload->toArray())->toBe($array);
});

test('the getCleanupThresholdHours method returns the value of the correct property', function () {
    expect($this->payload->getCleanupThresholdHours())->toBe(12);
});

test('the getCleanupThresholdHours method returns 0 if the value is set to negative', function () {
    $payload = new CleanOrphanedUploads(
        'test_disk',
        'test_path',
        -1
    );

    expect($payload->getCleanupThresholdHours())->toBe(0);
});

test('cleanup threshold defaults to 24 hours', function () {
    $payload = new CleanOrphanedUploads('disk', 'path');

    expect($payload->getCleanupThresholdHours())->toBe(24);
});

test('isCleanupEnabled returns the expected value', function (bool $enabled) {
    config()->set('model-filer.cleanup.enabled', $enabled);

    expect($this->payload->isCleanupEnabled())->toBe($enabled);
})->with([true, false]);

test('isDryRun returns the expected value', function (bool $enabled) {
    config()->set('model-filer.cleanup.dry_run', $enabled);

    expect($this->payload->isDryRun())->toBe($enabled);
})->with([true, false]);
