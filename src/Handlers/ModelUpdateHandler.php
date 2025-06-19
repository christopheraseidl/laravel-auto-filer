<?php

namespace christopheraseidl\ModelFiler\Handlers;

use christopheraseidl\ModelFiler\Handlers\Traits\CreatesDeleteJob;
use christopheraseidl\ModelFiler\Handlers\Traits\CreatesMoveJob;
use Illuminate\Database\Eloquent\Model;

/**
 * Handles file uploads and deletions when models are updated.
 */
class ModelUpdateHandler extends BaseModelEventHandler
{
    use CreatesDeleteJob, CreatesMoveJob;

    /**
     * Get jobs only for attributes that have been modified.
     */
    protected function getAllJobs(Model $model, ?\Closure $filter = null): array
    {
        return parent::getAllJobs(
            $model,
            $filter ?? fn ($model, $attribute) => $model->isDirty($attribute)
        );
    }

    /**
     * Create delete and move jobs based on file changes during update.
     */
    protected function createJobsFromAttribute(Model $model, string $attribute, ?string $type = null): ?array
    {
        $removedFiles = $this->fileTracker->getRemovedFiles($model, $attribute);
        $newFiles = $this->fileTracker->getNewFiles($model, $attribute);

        $deleteJob = $this->createDeleteJob($this->builder, $model, $attribute, $type, $this->disk, $removedFiles);

        $moveJob = $this->createMoveJob($this->builder, $model, $attribute, $type, $this->disk, $newFiles);

        return array_filter([$deleteJob, $moveJob]);
    }

    protected function getBatchDescription(): string
    {
        return 'Handle uploads for model update.';
    }
}
