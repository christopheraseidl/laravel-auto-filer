<?php

namespace christopheraseidl\HasUploads;

use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager as BatchManagerContract;
use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker as ModelFileChangeTrackerContract;
use christopheraseidl\HasUploads\Handlers\Services\BatchManager;
use christopheraseidl\HasUploads\Handlers\Services\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Jobs\CleanOrphanedUploads as CleanOrphanedUploadsJob;
use christopheraseidl\HasUploads\Jobs\Contracts\Builder as BuilderContract;
use christopheraseidl\HasUploads\Jobs\Contracts\BuilderValidator as BuilderValidatorContract;
use christopheraseidl\HasUploads\Jobs\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsJobContract;
use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryJobContract;
use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploads as DeleteUploadsJobContract;
use christopheraseidl\HasUploads\Jobs\Contracts\MoveUploads as MoveUploadsJobContract;
use christopheraseidl\HasUploads\Jobs\DeleteUploadDirectory as DeleteUploadDirectoryJob;
use christopheraseidl\HasUploads\Jobs\DeleteUploads as DeleteUploadsJob;
use christopheraseidl\HasUploads\Jobs\MoveUploads as MoveUploadsJob;
use christopheraseidl\HasUploads\Jobs\Services\Builder;
use christopheraseidl\HasUploads\Jobs\Validators\BuilderValidator;
use christopheraseidl\HasUploads\Payloads\CleanOrphanedUploads as CleanOrphanedUploadsPayload;
use christopheraseidl\HasUploads\Payloads\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsPayloadContract;
use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryPayloadContract;
use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploads as DeleteUploadsPayloadContract;
use christopheraseidl\HasUploads\Payloads\Contracts\MoveUploads as MoveUploadsPayloadContract;
use christopheraseidl\HasUploads\Payloads\DeleteUploadDirectory as DeleteUploadDirectoryPayload;
use christopheraseidl\HasUploads\Payloads\DeleteUploads as DeleteUploadsPayload;
use christopheraseidl\HasUploads\Payloads\MoveUploads as MoveUploadsPayload;
use christopheraseidl\HasUploads\Services\Contracts\UploadService as UploadServiceContract;
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
        // Services
        $this->app->singleton(UploadServiceContract::class, UploadService::class);
        $this->app->bind(ModelFileChangeTrackerContract::class, ModelFileChangeTracker::class);
        $this->app->bind(BuilderContract::class, Builder::class);
        $this->app->bind(BatchManagerContract::class, BatchManager::class);
        $this->app->bind(BuilderValidatorContract::class, BuilderValidator::class);

        // Jobs
        $this->app->bind(CleanOrphanedUploadsJobContract::class, CleanOrphanedUploadsJob::class);
        $this->app->bind(DeleteUploadDirectoryJobContract::class, DeleteUploadDirectoryJob::class);
        $this->app->bind(DeleteUploadsJobContract::class, DeleteUploadsJob::class);
        $this->app->bind(MoveUploadsJobContract::class, MoveUploadsJob::class);

        // Payloads
        $this->app->bind(CleanOrphanedUploadsPayloadContract::class, CleanOrphanedUploadsPayload::class);
        $this->app->bind(DeleteUploadDirectoryPayloadContract::class, DeleteUploadDirectoryPayload::class);
        $this->app->bind(DeleteUploadsPayloadContract::class, DeleteUploadsPayload::class);
        $this->app->bind(MoveUploadsPayloadContract::class, MoveUploadsPayload::class);
    }
}
