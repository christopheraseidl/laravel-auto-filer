<?php

namespace christopheraseidl\ModelFiler\Tests\ModelFiler;

use Illuminate\Support\Str;

it('gets the model directory name', function () {
    $name = Str::snake(
        Str::plural(class_basename($this->model))
    );

    $result = $this->model->getModelDirName();

    expect($result)->toBe($name);
});
