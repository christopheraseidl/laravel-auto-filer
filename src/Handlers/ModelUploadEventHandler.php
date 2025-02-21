<?php

namespace christopheraseidl\HasUploads\Handlers;

use christopheraseidl\HasUploads\Contracts\BatchHandler;
use christopheraseidl\HasUploads\Contracts\Builder;
use christopheraseidl\HasUploads\Contracts\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Contracts\UploadService;
use Closure;
use Illuminate\Database\Eloquent\Model;

abstract class ModelUploadEventHandler
{
    protected string $disk;

    // abstract protected function getAllJobs(Model $model): array;

    protected function getAllJobs(Model $model, ?Closure $filter = null): array
    {
        return collect($model->getUploadableAttributes())
            ->filter(fn ($type, $attribute) => $filter($model, $attribute))
            ->flatMap(fn ($type, $attribute) => $this->createJobsFromAttribute($model, $attribute, $type)
            )
            ->filter()
            ->all();
    }

    abstract protected function createJobsFromAttribute(Model $model, string $attribute, ?string $type = null): ?array;

    abstract protected function getBatchDescription(): string;

    public function __construct(
        protected UploadService $uploadService,
        protected Builder $builder,
        protected BatchHandler $batch,
        protected ModelFileChangeTracker $fileTracker
    ) {
        $this->disk = $uploadService->getDisk();
    }

    public function handle(Model $model): void
    {
        $jobs = $this->getAllJobs($model);

        if (empty($jobs)) {
            return;
        }

        $this->batch->dispatch(
            $jobs,
            $model,
            $this->disk,
            $this->getBatchDescription()
        );
    }
}
