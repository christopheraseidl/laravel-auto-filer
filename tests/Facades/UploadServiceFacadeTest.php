<?php

namespace christopheraseidl\HasUploads\Tests\Facades;

use christopheraseidl\HasUploads\Facades\UploadService;

beforeEach(function () {
    $this->reflection = new \ReflectionClass(UploadService::class);
});

it('extends the Facade class', function () {
    expect($this->reflection->getParentClass()->getName())
        ->toBe('Illuminate\Support\Facades\Facade');
});

it('returns the expected accessor value', function () {
    $method = $this->reflection->getMethod('getFacadeAccessor');

    $instance = new UploadService;
    $accessor = $method->invoke($instance);

    expect($accessor)->toBe('christopheraseidl\HasUploads\Services\UploadService');
});
