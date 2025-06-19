<?php

namespace christopheraseidl\ModelFiler\Handlers;

use christopheraseidl\ModelFiler\Handlers\Contracts\BatchManager;
use christopheraseidl\ModelFiler\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\ModelFiler\Jobs\Contracts\Builder;
use christopheraseidl\ModelFiler\Services\Contracts\FileService;
use Illuminate\Database\Eloquent\Model;

/**
 * Handles model events by creating and dispatching upload jobs.
 */
abstract class BaseModelEventHandler
{
    protected string $disk;

    abstract protected function createJobsFromAttribute(Model $model, string $attribute, ?string $type = null): ?array;

    abstract protected function getBatchDescription(): string;

    public function __construct(
        protected FileService $fileService,
        protected Builder $builder,
        protected BatchManager $batch,
        protected ModelFileChangeTracker $fileTracker
    ) {
        $this->disk = $fileService->getDisk();
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

    /**
     * Collect jobs from all uploadable attributes, applying optional filter.
     */
    protected function getAllJobs(Model $model, ?\Closure $filter = null): array
    {
        // Default filter accepts all attributes
        $filter = $filter ?? fn ($model, $attribute) => true;

        return collect($model->getUploadableAttributes())
            ->filter(fn ($type, $attribute) => $filter($model, $attribute))
            ->flatMap(fn ($type, $attribute) => $this->createJobsFromAttribute($model, $attribute, $type)
            )
            ->filter()
            ->all();
    }
}
