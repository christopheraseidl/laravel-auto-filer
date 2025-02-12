<?php

namespace christopheraseidl\HasUploads\Tests\HasUploads;

use christopheraseidl\Reflect\Reflect;
use Exception;

it('correctly validates the model asset type', function () {
    $exists = 'images';
    $nonExistent = 'non-existent';

    expect(fn () => Reflect::on($this->model)->validateAssetType($exists))
        ->not->toThrow(Exception::class);

    expect(fn () => Reflect::on($this->model)->validateAssetType(null))
        ->not->toThrow(Exception::class);

    expect(fn () => Reflect::on($this->model)->validateAssetType($nonExistent))
        ->toThrow(Exception::class, "The asset type '{$nonExistent}' does not exist.");
});
