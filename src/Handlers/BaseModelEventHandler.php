<?php

namespace christopheraseidl\ModelFiler\Handlers;

use christopheraseidl\ModelFiler\Handlers\Contracts\BatchManager;
use christopheraseidl\ModelFiler\Handlers\Contracts\ModelEventHandler;
use christopheraseidl\ModelFiler\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\ModelFiler\Jobs\Contracts\Builder;
use christopheraseidl\ModelFiler\Services\Contracts\FileService;
use Illuminate\Database\Eloquent\Model;

/**
 * Handles model events by creating and dispatching upload jobs.
 */
abstract class BaseModelEventHandler implements ModelEventHandler
{
    protected string $disk;

    public function __construct(
        protected FileService $fileService,
        protected Builder $builder,
        protected BatchManager $batch,
        protected ModelFileChangeTracker $fileTracker
    ) {
        $this->disk = $fileService->getDisk();
    }

    /**
     * Handle model events for file processing.
     */
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
     * Get all jobs from uploadable attributes with optional filtering.
     */
    public function getAllJobs(Model $model, ?\Closure $filter = null): array
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

    /**
     * Create jobs from model attribute for file processing.
     */
    abstract public function createJobsFromAttribute(Model $model, string $attribute, ?string $type = null): ?array;

    /**
     * Get batch description for job processing.
     */
    abstract public function getBatchDescription(): string;
}
