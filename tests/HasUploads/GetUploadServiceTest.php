<?php

namespace christopheraseidl\HasUploads\Tests\HasUploads;

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\Reflect\Reflect;

it('gets the upload service', function () {
    $service = Reflect::on($this->model)->getUploadService();

    expect($service)->toBeInstanceOf(UploadService::class);
});
