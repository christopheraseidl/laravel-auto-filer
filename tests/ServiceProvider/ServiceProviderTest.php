<?php

namespace christopheraseidl\AutoFiler\Tests\ServiceProvider;

use christopheraseidl\AutoFiler\Actions\GenerateThumbnailAction;
use christopheraseidl\AutoFiler\AutoFilerServiceProvider;
use christopheraseidl\AutoFiler\Contracts\FileDeleter;
use christopheraseidl\AutoFiler\Contracts\FileMover;
use christopheraseidl\AutoFiler\Contracts\GenerateThumbnail;
use christopheraseidl\AutoFiler\Contracts\ManifestBuilder;
use christopheraseidl\AutoFiler\Contracts\RichTextScanner;
use christopheraseidl\AutoFiler\Services\FileDeleterService;
use christopheraseidl\AutoFiler\Services\FileMoverService;
use christopheraseidl\AutoFiler\Services\ManifestBuilderService;
use christopheraseidl\AutoFiler\Services\RichTextScannerService;
use christopheraseidl\CircuitBreaker\CircuitBreaker;
use christopheraseidl\CircuitBreaker\CircuitBreakerFactory;
use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->app = app();
    $this->provider = new AutoFilerServiceProvider($this->app);
});

it('registers all service contracts as singletons', function () {
    $this->provider->packageRegistered();

    expect($this->app->make(ManifestBuilder::class))->toBeInstanceOf(ManifestBuilderService::class);
    expect($this->app->make(FileMover::class))->toBeInstanceOf(FileMoverService::class);
    expect($this->app->make(RichTextScanner::class))->toBeInstanceOf(RichTextScannerService::class);
    expect($this->app->make(FileDeleter::class))->toBeInstanceOf(FileDeleterService::class);
    expect($this->app->make(CircuitBreakerContract::class))->toBeInstanceOf(CircuitBreaker::class);
    expect($this->app->make(GenerateThumbnail::class))->toBeInstanceOf(GenerateThumbnailAction::class);

    // Verify singletons
    expect($this->app->make(ManifestBuilder::class))
        ->toBe($this->app->make(ManifestBuilder::class));

    expect($this->app->make(FileMover::class))
        ->toBe($this->app->make(FileMover::class));

    expect($this->app->make(RichTextScanner::class))
        ->toBe($this->app->make(RichTextScanner::class));

    expect($this->app->make(FileDeleter::class))
        ->toBe($this->app->make(FileDeleter::class));

    expect($this->app->make(CircuitBreakerContract::class))
        ->toBe($this->app->make(CircuitBreakerContract::class));

    expect($this->app->make(GenerateThumbnail::class))
        ->toBe($this->app->make(GenerateThumbnail::class));
});

it('registers circuit breaker contract', function () {
    $factory = $this->mock(CircuitBreakerFactory::class);
    $circuitBreaker = $this->mock(CircuitBreaker::class);

    $factory->shouldReceive('make')
        ->once()
        ->with('auto-filer-circuit-breaker')
        ->andReturn($circuitBreaker);

    $this->app->instance(CircuitBreakerFactory::class, $factory);

    // Re-run the service container bindings with the mocks in place
    $this->provider->packageRegistered();

    expect($this->app->make(CircuitBreakerContract::class))->toBe($circuitBreaker);
});

it('adds pascal macro when method does not exist', function () {
    $provider = \Mockery::mock(AutoFilerServiceProvider::class, [$this->app])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    // Mock hasPascalMethod to force call of addPascalMacro
    $provider->shouldReceive('hasPascalMethod')->andReturnFalse();

    // Clear any existing macros
    Str::flushMacros();

    $provider->packageBooted();

    // Expose macros to public scope
    $str = new class extends Str
    {
        public static function getMacros(): array
        {
            return self::$macros;
        }
    };

    $macros = $str->getMacros();
    expect($macros)->toHaveKey('pascal');

    $pascalMacro = $macros['pascal'];

    expect($pascalMacro('hello_world'))->toBe('HelloWorld');
    expect($pascalMacro('test-case'))->toBe('TestCase');
    expect($pascalMacro('studly_alias'))->toBe(Str::studly('studly_alias'));
});
