<?php

namespace christopheraseidl\ModelFiler;

use christopheraseidl\ModelFiler\Handlers\Contracts\BatchManager as BatchManagerContract;
use christopheraseidl\ModelFiler\Handlers\Contracts\ModelFileChangeTracker as ModelFileChangeTrackerContract;
use christopheraseidl\ModelFiler\Handlers\Services\BatchManager;
use christopheraseidl\ModelFiler\Handlers\Services\ModelFileChangeTracker;
use christopheraseidl\ModelFiler\Jobs\CleanOrphanedUploads as CleanOrphanedUploadsJob;
use christopheraseidl\ModelFiler\Jobs\Contracts\Builder as BuilderContract;
use christopheraseidl\ModelFiler\Jobs\Contracts\BuilderValidator as BuilderValidatorContract;
use christopheraseidl\ModelFiler\Jobs\Contracts\CircuitBreaker as CircuitBreakerContract;
use christopheraseidl\ModelFiler\Jobs\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsJobContract;
use christopheraseidl\ModelFiler\Jobs\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryJobContract;
use christopheraseidl\ModelFiler\Jobs\Contracts\DeleteUploads as DeleteUploadsJobContract;
use christopheraseidl\ModelFiler\Jobs\Contracts\FileDeleter as FileDeleterContract;
use christopheraseidl\ModelFiler\Jobs\Contracts\FileMover as FileMoverContract;
use christopheraseidl\ModelFiler\Jobs\Contracts\MoveUploads as MoveUploadsJobContract;
use christopheraseidl\ModelFiler\Jobs\DeleteUploadDirectory as DeleteUploadDirectoryJob;
use christopheraseidl\ModelFiler\Jobs\DeleteUploads as DeleteUploadsJob;
use christopheraseidl\ModelFiler\Jobs\MoveUploads as MoveUploadsJob;
use christopheraseidl\ModelFiler\Jobs\Services\Builder;
use christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker;
use christopheraseidl\ModelFiler\Jobs\Services\FileDeleter;
use christopheraseidl\ModelFiler\Jobs\Services\FileMover;
use christopheraseidl\ModelFiler\Jobs\Validators\BuilderValidator;
use christopheraseidl\ModelFiler\Payloads\CleanOrphanedUploads as CleanOrphanedUploadsPayload;
use christopheraseidl\ModelFiler\Payloads\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsPayloadContract;
use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryPayloadContract;
use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploads as DeleteUploadsPayloadContract;
use christopheraseidl\ModelFiler\Payloads\Contracts\MoveUploads as MoveUploadsPayloadContract;
use christopheraseidl\ModelFiler\Payloads\DeleteUploadDirectory as DeleteUploadDirectoryPayload;
use christopheraseidl\ModelFiler\Payloads\DeleteUploads as DeleteUploadsPayload;
use christopheraseidl\ModelFiler\Payloads\MoveUploads as MoveUploadsPayload;
use christopheraseidl\ModelFiler\Services\Contracts\FileService as FileServiceContract;
use christopheraseidl\ModelFiler\Services\FileService;
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
        $this->registerServices();
        $this->registerJobs();
        $this->registerPayloads();
    }

    /**
     * Call methods that should run when the package is booted.
     */
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
        $this->app->singleton(FileServiceContract::class, FileService::class);
        $this->app->singleton(ModelFileChangeTrackerContract::class, ModelFileChangeTracker::class);
        $this->app->singleton(BuilderContract::class, Builder::class);
        $this->app->singleton(BatchManagerContract::class, BatchManager::class);
        $this->app->singleton(BuilderValidatorContract::class, BuilderValidator::class);
        $this->registerCircuitBreaker();
        $this->app->bind(FileMoverContract::class, FileMover::class);
        $this->app->singleton(FileDeleterContract::class, FileDeleter::class);
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

    /**
     * Register circuit breaker with configuration-based parameters.
     */
    protected function registerCircuitBreaker(): void
    {
        $this->app->singleton(CircuitBreakerContract::class, function (Application $app) {
            return new CircuitBreaker(
                name: 'laravel-model-filer-circuit-breaker',
                failureThreshold: config('model-filer.circuit_breaker.failure_threshold', 5),
                recoveryTimeout: config('model-filer.circuit_breaker.recovery_timeout', 60),
                halfOpenMaxAttempts: config('model-filer.circuit_breaker.half_open_attempts', 3),
                cacheTtlHours: config('model-filer.circuit_breaker.cache_ttl', 1),
                emailNotificationEnabled: config('model-filer.circuit_breaker.email_notifications', false),
                adminEmail: config('model-filer.circuit_breaker.admin_email')
            );
        });
    }

    /**
     * Add Str::pascal macro for Laravel 10 compatibility if method doesn't exist.
     */
    protected function addPascalMacroIfNeeded(): void
    {
        if (! $this->hasPascalMethod()) {
            // Laravel 10 compatibility patch
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
