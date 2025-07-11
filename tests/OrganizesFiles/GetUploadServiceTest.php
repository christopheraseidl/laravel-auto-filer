<?php

namespace christopheraseidl\ModelFiler\Tests\ModelFiler;

use christopheraseidl\ModelFiler\Services\Contracts\FileService;
use christopheraseidl\Reflect\Reflect;

it('gets the upload service', function () {
    $service = Reflect::on($this->model)->getFileService();

    expect($service)->toBeInstanceOf(FileService::class);
});
