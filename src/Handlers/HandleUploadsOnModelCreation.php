<?php

namespace christopheraseidl\HasUploads\Handlers;

use christopheraseidl\HasUploads\Traits\CreatesMoveJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class HandleUploadsOnModelCreation extends ModelUploadEventHandler
{
    use CreatesMoveJob;

    protected function getAllJobs(Model $model): array
    {
        return collect($model->getUploadableAttributes())
            ->filter(fn ($type, $attribute) => ! is_null($model->$attribute))
            ->flatMap(fn ($type, $attribute) => $this->createJobsFromAttribute($model, $attribute, $type)
            )
            ->filter()
            ->all();
    }

    protected function createJobsFromAttribute(Model $model, string $attribute, ?string $type = null): ?array
    {
        $newFiles = $model->$attribute;

        if (is_null($newFiles)) {
            return null;
        }

        $job = $this->createMoveJob($model, $attribute, $type, Arr::wrap($newFiles));

        return Arr::wrap($job);
    }

    protected function getBatchDescription(): string
    {
        return 'Handle uploads for model creation.';
    }
}
