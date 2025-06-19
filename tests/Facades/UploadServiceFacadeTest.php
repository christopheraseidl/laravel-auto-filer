<?php

namespace christopheraseidl\ModelFiler\Tests\Facades;

use christopheraseidl\ModelFiler\Facades\FileService;

/**
 * Tests configuration of FileService Facade class.
 *
 * @covers \christopheraseidl\ModelFiler\Facades\FileService
 */
beforeEach(function () {
    $this->reflection = new \ReflectionClass(FileService::class);
});

it('extends the Facade class', function () {
    expect($this->reflection->getParentClass()->getName())
        ->toBe('Illuminate\Support\Facades\Facade');
});

it('returns the expected accessor value', function () {
    $method = $this->reflection->getMethod('getFacadeAccessor');

    $instance = new FileService;
    $accessor = $method->invoke($instance);

    expect($accessor)->toBe('christopheraseidl\ModelFiler\Services\FileService');
});
