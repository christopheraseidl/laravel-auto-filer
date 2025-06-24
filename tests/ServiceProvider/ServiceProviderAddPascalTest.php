<?php

namespace christopheraseidl\ModelFiler\Tests\ServiceProvider;

use christopheraseidl\ModelFiler\ModelFilerServiceProvider;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Str;

/**
 * Tests the service provider's ability to add a pascal method to
 * Illuminate\Support\Str only when needed. This makes the package compatible
 * with Laravel 10, which lacks a pascal method, having only studly().
 *
 * @covers \christopheraseidl\ModelFiler\ModelFilerServiceProvider
 */
it('adds a pascal macro when needed', function () {
    $string = 'hello_world';
    Str::flushMacros();

    $provider = Reflect::on(new class(app()) extends ModelFilerServiceProvider
    {
        protected function hasPascalMethod(): bool
        {
            return false; // Simulate Laravel 10
        }
    });

    $provider->addPascalMacroIfNeeded();

    expect(Str::hasMacro('pascal'))->toBeTrue();
    expect(Str::pascal($string))->toBe('HelloWorld');
    expect(Str::pascal($string))->toBe(Str::studly($string));
});

it('does not add a pascal macro when method already exists', function () {
    Str::flushMacros();

    $provider = Reflect::on(new class(app()) extends ModelFilerServiceProvider
    {
        public $addPascalMacroCalled = false;

        protected function hasPascalMethod(): bool
        {
            return true; // Simulate Laravel 11+
        }

        protected function addPascalMacro(): void
        {
            $this->addPascalMacroCalled = true;
            parent::addPascalMacro();
        }
    });

    $provider->addPascalMacroIfNeeded();

    expect($provider->addPascalMacroCalled)->toBeFalse();
    expect(Str::hasMacro('pascal'))->toBeFalse();
});

it('uses pascal as an alias for studly', function () {
    $string = 'hello_world';

    $provider = Reflect::on(new ModelFilerServiceProvider(app()));

    $transformedString = $provider->pascalTransform($string);

    expect($transformedString)->toBe(Str::studly($string));
    expect($transformedString)->toBe('HelloWorld');
});
