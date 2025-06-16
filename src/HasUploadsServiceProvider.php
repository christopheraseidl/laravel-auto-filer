<?php

namespace christopheraseidl\HasUploads;

use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager as BatchManagerContract;
use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker as ModelFileChangeTrackerContract;
use christopheraseidl\HasUploads\Handlers\Services\BatchManager;
use christopheraseidl\HasUploads\Handlers\Services\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Jobs\CleanOrphanedUploads as CleanOrphanedUploadsJob;
use christopheraseidl\HasUploads\Jobs\Contracts\Builder as BuilderContract;
use christopheraseidl\HasUploads\Jobs\Contracts\BuilderValidator as BuilderValidatorContract;
use christopheraseidl\HasUploads\Jobs\Contracts\CircuitBreaker as CircuitBreakerContract;
use christopheraseidl\HasUploads\Jobs\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsJobContract;
use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryJobContract;
use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploads as DeleteUploadsJobContract;
use christopheraseidl\HasUploads\Jobs\Contracts\FileDeleter as FileDeleterContract;
use christopheraseidl\HasUploads\Jobs\Contracts\FileMover as FileMoverContract;
use christopheraseidl\HasUploads\Jobs\Contracts\MoveUploads as MoveUploadsJobContract;
use christopheraseidl\HasUploads\Jobs\DeleteUploadDirectory as DeleteUploadDirectoryJob;
use christopheraseidl\HasUploads\Jobs\DeleteUploads as DeleteUploadsJob;
use christopheraseidl\HasUploads\Jobs\MoveUploads as MoveUploadsJob;
use christopheraseidl\HasUploads\Jobs\Services\Builder;
use christopheraseidl\HasUploads\Jobs\Services\CircuitBreaker;
use christopheraseidl\HasUploads\Jobs\Services\FileDeleter;
use christopheraseidl\HasUploads\Jobs\Services\FileMover;
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
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
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
        $this->registerServices();
        $this->registerJobs();
        $this->registerPayloads();
    }

    public function packageBooted()
    {
        $this->addPascalMacroIfNeeded();
    }

    public function pascalTransform($value)
    {
        return Str::studly($value);
    }

    protected function registerServices(): void
    {
        $this->app->singleton(UploadServiceContract::class, UploadService::class);
        $this->app->singleton(ModelFileChangeTrackerContract::class, ModelFileChangeTracker::class);
        $this->app->singleton(BuilderContract::class, Builder::class);
        $this->app->singleton(BatchManagerContract::class, BatchManager::class);
        $this->app->singleton(BuilderValidatorContract::class, BuilderValidator::class);
        $this->registerCircuitBreaker();
        $this->app->bind(FileMoverContract::class, FileMover::class);
        $this->app->singleton(FileDeleterContract::class, FileDeleter::class);
    }

    protected function registerCircuitBreaker(): void
    {
        $this->app->singleton(CircuitBreakerContract::class, function (Application $app) {
            return new CircuitBreaker(
                name: 'laravel-has-uploads-circuit-breaker',
                failureThreshold: config('has-uploads.circuit_breaker.failure_threshold', 5),
                recoveryTimeout: config('has-uploads.circuit_breaker.recovery_timeout', 60),
                halfOpenMaxAttempts: config('has-uploads.circuit_breaker.half_open_attempts', 3),
                cacheTtlHours: config('has-uploads.circuit_breaker.cache_ttl', 1),
                emailNotificationEnabled: config('has-uploads.circuit_breaker.email_notifications', false),
                adminEmail: config('has-uploads.circuit_breaker.admin_email')
            );
        });
    }

    protected function registerJobs(): void
    {
        $this->app->bind(CleanOrphanedUploadsJobContract::class, CleanOrphanedUploadsJob::class);
        $this->app->bind(DeleteUploadDirectoryJobContract::class, DeleteUploadDirectoryJob::class);
        $this->app->bind(DeleteUploadsJobContract::class, DeleteUploadsJob::class);
        $this->app->bind(MoveUploadsJobContract::class, MoveUploadsJob::class);
    }

    protected function registerPayloads(): void
    {
        $this->app->bind(CleanOrphanedUploadsPayloadContract::class, CleanOrphanedUploadsPayload::class);
        $this->app->bind(DeleteUploadDirectoryPayloadContract::class, DeleteUploadDirectoryPayload::class);
        $this->app->bind(DeleteUploadsPayloadContract::class, DeleteUploadsPayload::class);
        $this->app->bind(MoveUploadsPayloadContract::class, MoveUploadsPayload::class);
    }

    // Patch for Str::pascal method compatibility in Laravel 10.
    protected function addPascalMacroIfNeeded(): void
    {
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
        Str::macro('pascal', [$this, 'pascalTransform']);
    }
}
