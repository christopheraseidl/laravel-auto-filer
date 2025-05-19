<?php

namespace christopheraseidl\HasUploads\Tests\Contracts;

use christopheraseidl\HasUploads\Contracts\UploadService;

/**
 * Tests UploadService interface structure.
 *
 * @covers \christopheraseidl\HasUploads\Contracts\UploadService
 */
beforeEach(function () {
    $this->interface = new \ReflectionClass(UploadService::class);
});

it('is an interface', function () {
    expect($this->interface->isInterface())->toBeTrue();
});

test('the getDisk method has no parameters and returns a string', function () {
    $name = 'getDisk';
    $method = $this->interface->getMethod($name);

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($method->getParameters())->toBeEmpty()
        ->and($method->getReturnType()->getName())->toBe('string');
});

test('the getPath method has no parameters and returns a string', function () {
    $name = 'getPath';
    $method = $this->interface->getMethod($name);

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($method->getParameters())->toBeEmpty()
        ->and($method->getReturnType()->getName())->toBe('string');
});

test('the storeFile method has the correct parameters and returns a string', function () {
    $name = 'storeFile';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $model = $parameters[0];
    $file = $parameters[1];
    $assetType = $parameters[2];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($model->getName())->toBe('model')
        ->and($model->getType()->getName())->toBe('Illuminate\Database\Eloquent\Model')
        ->and($file->getName())->toBe('file')
        ->and($file->getType()->getName())->toBe('Illuminate\Http\UploadedFile')
        ->and($assetType->getName())->toBe('assetType')
        ->and($assetType->getDefaultValue())->toBe('')
        ->and($assetType->getType()->getName())->toBe('string')
        ->and($method->getReturnType()->getName())->toBe('string');
});

test('the validateUpload method has the correct parameter and returns void', function () {
    $name = 'validateUpload';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $file = $parameters[0];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($file->getName())->toBe('file')
        ->and($file->getType()->getName())->toBe('Illuminate\Http\UploadedFile')
        ->and($method->getReturnType()->getName())->toBe('void');
});

test('the moveFile method has the correct parameters and returns a string', function () {
    $name = 'moveFile';
    $method = $this->interface->getMethod($name);
    $parameters = $method->getParameters();
    $oldPath = $parameters[0];
    $newDir = $parameters[1];

    expect($this->interface->hasMethod($name))->toBeTrue()
        ->and($oldPath->getName())->toBe('oldPath')
        ->and($oldPath->getType()->getName())->toBe('string')
        ->and($newDir->getName())->toBe('newDir')
        ->and($newDir->getType()->getName())->toBe('string')
        ->and($method->getReturnType()->getName())->toBe('string');
});
