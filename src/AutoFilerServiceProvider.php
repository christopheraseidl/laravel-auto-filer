<?php

namespace christopheraseidl\AutoFiler;

use christopheraseidl\AutoFiler\Actions\GenerateThumbnailAction;
use christopheraseidl\AutoFiler\Contracts\FileDeleter;
use christopheraseidl\AutoFiler\Contracts\FileMover;
use christopheraseidl\AutoFiler\Contracts\GenerateThumbnail;
use christopheraseidl\AutoFiler\Contracts\ManifestBuilder;
use christopheraseidl\AutoFiler\Contracts\RichTextScanner;
use christopheraseidl\AutoFiler\Services\FileDeleterService;
use christopheraseidl\AutoFiler\Services\FileMoverService;
use christopheraseidl\AutoFiler\Services\ManifestBuilderService;
use christopheraseidl\AutoFiler\Services\RichTextScannerService;
use christopheraseidl\CircuitBreaker\CircuitBreakerFactory;
use christopheraseidl\CircuitBreaker\Contracts\CircuitBreakerContract;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Registers all package services, jobs, payloads, and compatibility features.
 */
class AutoFilerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-auto-filer')
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

            return $factory->make('auto-filer-circuit-breaker');
        });
        $this->app->singleton(GenerateThumbnail::class, GenerateThumbnailAction::class);
    }

    /**
     * Call methods that should run when the package is booted.
     */
    public function packageBooted()
    {
        // Add Str::pascal macro for Laravel 10 compatibility if method doesn't exist.
        if (! $this->hasPascalMethod()) {
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
