<?php

namespace christopheraseidl\HasUploads;

use christopheraseidl\HasUploads\Contracts\BatchHandler as BatchHandlerContract;
use christopheraseidl\HasUploads\Contracts\JobBuilder as JobBuilderContract;
use christopheraseidl\HasUploads\Contracts\JobBuilderValidator as JobBuilderValidatorContract;
use christopheraseidl\HasUploads\Contracts\ModelFileChangeTracker as ModelFileChangeTrackerContract;
use christopheraseidl\HasUploads\Contracts\UploadService as UploadServiceContract;
use christopheraseidl\HasUploads\Jobs\Services\BatchHandler;
use christopheraseidl\HasUploads\Jobs\Services\JobBuilder;
use christopheraseidl\HasUploads\Jobs\Services\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Jobs\Validators\JobBuilderValidator;
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

        $this->app->bind(JobBuilderContract::class, JobBuilder::class);

        $this->app->bind(BatchHandlerContract::class, BatchHandler::class);

        $this->app->bind(JobBuilderValidatorContract::class, JobBuilderValidator::class);
    }
}
