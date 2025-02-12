<?php

namespace christopheraseidl\HasUploads;

use christopheraseidl\HasUploads\Contracts\ModelFileChangeTracker as ModelFileChangeTrackerContract;
use christopheraseidl\HasUploads\Contracts\UploadService as UploadServiceContract;
use christopheraseidl\HasUploads\Jobs\Services\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Support\UploadService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class HasUploadsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-has-uploads')
            ->hasConfigFile();
    }

    public function packageRegistered()
    {
        $this->app->singleton(UploadServiceContract::class, UploadService::class);

        $this->app->bind(ModelFileChangeTrackerContract::class, ModelFileChangeTracker::class);
    }
}
