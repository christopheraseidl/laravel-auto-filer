<?php

namespace christopheraseidl\ModelFiler\Tests\Payloads;

use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryContract;
use christopheraseidl\ModelFiler\Payloads\DeleteUploadDirectory;

/**
 * Tests DeleteUploadDirectory structure and behavior.
 *
 * @covers \christopheraseidl\ModelFiler\Payloads\DeleteUploadDirectory
 */
beforeEach(function () {
    $this->class = $this->model::class;

    $this->payload = new DeleteUploadDirectory(
        $this->class,
        $this->model->id,
        'public_disk',
        'public/path'
    );
});

it('implements the DeleteUploadDirectory contract', function () {
    expect($this->payload)->toBeInstanceOf(DeleteUploadDirectoryContract::class);
});

test('the getKey method returns the expected value', function () {
    expect($this->payload->getKey())->toBe("delete_upload_directory_{$this->class}_{$this->model->id}");
});

test('the shouldBroadcastIndividualEvents method returns true', function () {
    expect($this->payload->shouldBroadcastIndividualEvents())->toBeTrue();
});

test('the toArray method returns the expected array', function () {
    $array = [
        'modelClass' => $this->class,
        'id' => $this->model->id,
        'path' => 'public/path',
    ];

    expect($this->payload->toArray())->toBe($array);
});

test('the getModelClass method returns the expected value', function () {
    expect($this->payload->getModelClass())->toBe($this->class);
});

test('the getId method returns the expected value', function () {
    expect($this->payload->getId())->toBe($this->model->id);
});
