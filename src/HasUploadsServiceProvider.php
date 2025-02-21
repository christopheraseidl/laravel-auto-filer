<?php

namespace christopheraseidl\HasUploads;

use christopheraseidl\HasUploads\Contracts\UploadService as UploadServiceContract;
use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager as BatchManagerContract;
use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker as ModelFileChangeTrackerContract;
use christopheraseidl\HasUploads\Handlers\Services\BatchManager;
use christopheraseidl\HasUploads\Handlers\Services\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Jobs\Contracts\Builder as BuilderContract;
use christopheraseidl\HasUploads\Jobs\Contracts\BuilderValidator as BuilderValidatorContract;
use christopheraseidl\HasUploads\Jobs\Services\Builder;
use christopheraseidl\HasUploads\Jobs\Validators\BuilderValidator;
use christopheraseidl\HasUploads\Services\UploadService;
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

        $this->app->bind(BuilderContract::class, Builder::class);

        $this->app->bind(BatchManagerContract::class, BatchManager::class);

        $this->app->bind(BuilderValidatorContract::class, BuilderValidator::class);
    }
}
