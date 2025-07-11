<?php

namespace christopheraseidl\ModelFiler\Tests\ModelFiler;

use christopheraseidl\Reflect\Reflect;
use Exception;

it('correctly validates the model asset type', function () {
    $exists = 'images';
    $nonExistent = 'non-existent';
    $class = $this->model::class;

    expect(fn () => Reflect::on($this->model)->validateAssetType($exists))
        ->not->toThrow(Exception::class);

    expect(fn () => Reflect::on($this->model)->validateAssetType(null))
        ->not->toThrow(Exception::class);

    expect(fn () => Reflect::on($this->model)->validateAssetType($nonExistent))
        ->toThrow(Exception::class, "Asset type '{$nonExistent}' is not configured for {$class}");
});
