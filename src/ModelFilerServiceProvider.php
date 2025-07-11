<?php

namespace christopheraseidl\ModelFiler;

use christopheraseidl\CircuitBreaker\CircuitBreakerFactory;
use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;
use christopheraseidl\ModelFiler\Contracts\FileDeleter;
use christopheraseidl\ModelFiler\Contracts\FileMover;
use christopheraseidl\ModelFiler\Contracts\ManifestBuilder;
use christopheraseidl\ModelFiler\Contracts\RichTextScanner;
use christopheraseidl\ModelFiler\Services\FileDeleterService;
use christopheraseidl\ModelFiler\Services\FileMoverService;
use christopheraseidl\ModelFiler\Services\ManifestBuilderService;
use christopheraseidl\ModelFiler\Services\RichTextScannerService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Registers all package services, jobs, payloads, and compatibility features.
 */
class ModelFilerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-model-filer')
            ->hasConfigFile();
    }

    /**
     * Call methods that should run when the package is registered.
     */
    public function packageRegistered()
    {
        $this->app->singleton(ManifestBuilder::class, ManifestBuilderService::class);
        $this->app->singleton(FileMover::class, FileMoverService::class);
        $this->app->singleton(RichTextScanner::class, RichTextScannerService::class);
        $this->app->singleton(FileDeleter::class, FileDeleterService::class);
        $this->app->singleton(CircuitBreakerContract::class, function (Application $app) {
            $factory = $app->make(CircuitBreakerFactory::class);
            
            return $factory->make('model-filer-circuit-breaker');
        });
    }

    /**
     * Call methods that should run when the package is booted.
     */
    public function packageBooted()
    {
        // Add Str::pascal macro for Laravel 10 compatibility if method doesn't exist.
        if (!$this->hasPascalMethod()) {
            $this->addPascalMacro();
        }
    }

    protected function hasPascalMethod(): bool
    {
        return method_exists(Str::class, 'pascal');
    }

    protected function addPascalMacro(): void
    {
        Str::macro('pascal', function ($value) {
            return Str::studly($value);
        });
    }
}
