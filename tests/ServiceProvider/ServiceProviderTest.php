<?php

namespace christopheraseidl\ModelFiler\Tests\ServiceProvider;

use christopheraseidl\CircuitBreaker\CircuitBreakerFactory;
use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;
use christopheraseidl\ModelFiler\Contracts\FileDeleter;
use christopheraseidl\ModelFiler\Contracts\FileMover;
use christopheraseidl\ModelFiler\Contracts\ManifestBuilder;
use christopheraseidl\ModelFiler\Contracts\RichTextScanner;
use christopheraseidl\ModelFiler\ModelFilerServiceProvider;
use christopheraseidl\ModelFiler\Services\FileDeleterService;
use christopheraseidl\ModelFiler\Services\FileMoverService;
use christopheraseidl\ModelFiler\Services\ManifestBuilderService;
use christopheraseidl\ModelFiler\Services\RichTextScannerService;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->app = app();
    $this->provider = new ModelFilerServiceProvider($this->app);
});

it('registers all service contracts as singletons', function () {
    $this->provider->packageRegistered();

    expect($this->app->make(ManifestBuilder::class))->toBeInstanceOf(ManifestBuilderService::class);
    expect($this->app->make(FileMover::class))->toBeInstanceOf(FileMoverService::class);
    expect($this->app->make(RichTextScanner::class))->toBeInstanceOf(RichTextScannerService::class);
    expect($this->app->make(FileDeleter::class))->toBeInstanceOf(FileDeleterService::class);

    // Verify singletons
    expect($this->app->make(ManifestBuilder::class))
        ->toBe($this->app->make(ManifestBuilder::class));
});

it('registers circuit breaker contract', function () {
    $factory = $this->mock(CircuitBreakerFactory::class);
    $circuitBreaker = $this->mock(CircuitBreakerContract::class);

    $factory->shouldReceive('make')
        ->once()
        ->with('model-filer-circuit-breaker')
        ->andReturn($circuitBreaker);

    $this->app->instance(CircuitBreakerFactory::class, $factory);

    $this->provider->packageRegistered();

    expect($this->app->make(CircuitBreakerContract::class))->toBe($circuitBreaker);
});

it('adds pascal macro when method does not exist', function () {
    // Remove the method if it exists (for testing)
    if (method_exists(Str::class, 'pascal')) {
        $this->markTestSkipped('Str::pascal already exists');
    }

    $this->provider->packageBooted();

    expect(Str::pascal('hello_world'))->toBe('HelloWorld');
    expect(Str::pascal('test-case'))->toBe('TestCase');
});

it('does not add pascal macro when method exists', function () {
    if (! method_exists(Str::class, 'pascal')) {
        $this->markTestSkipped('Str::pascal does not exist');
    }

    $originalMacroCount = count(Str::getMacros());

    $this->provider->packageBooted();

    expect(count(Str::getMacros()))->toBe($originalMacroCount);
});
