<?php

namespace christopheraseidl\HasUploads\Handlers;

use christopheraseidl\HasUploads\Handlers\Traits\CreatesMoveJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ModelCreationHandler extends BaseModelEventHandler
{
    use CreatesMoveJob;

    protected function getAllJobs(Model $model, ?\Closure $filter = null): array
    {
        return parent::getAllJobs(
            $model,
            $filter ?? fn ($model, $attribute) => ! is_null($model->$attribute)
        );
    }

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
