<?php

namespace christopheraseidl\HasUploads\Handlers;

use christopheraseidl\HasUploads\Handlers\Traits\CreatesMoveJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Handles file uploads when models are created.
 */
class ModelCreationHandler extends BaseModelEventHandler
{
    use CreatesMoveJob;

    /**
     * Get jobs only for attributes with non-null values.
     */
    protected function getAllJobs(Model $model, ?\Closure $filter = null): array
    {
        return parent::getAllJobs(
            $model,
            $filter ?? fn ($model, $attribute) => ! is_null($model->$attribute)
        );
    }

    /**
     * Create move jobs for files attached during model creation.
     */
    protected function createJobsFromAttribute(Model $model, string $attribute, ?string $type = null): ?array
    {
        $newFiles = $model->$attribute;

        if (is_null($newFiles)) {
            return null;
        }

        $job = $this->createMoveJob($this->builder, $model, $attribute, $type, $this->disk, Arr::wrap($newFiles));

        return Arr::wrap($job);
    }

    protected function getBatchDescription(): string
    {
        return 'Handle uploads for model creation.';
    }
}
