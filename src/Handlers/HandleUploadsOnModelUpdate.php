<?php

namespace christopheraseidl\HasUploads\Handlers;

use christopheraseidl\HasUploads\Traits\CreatesDeleteJob;
use christopheraseidl\HasUploads\Traits\CreatesMoveJob;
use Illuminate\Database\Eloquent\Model;

class HandleUploadsOnModelUpdate extends ModelUploadEventHandler
{
    use CreatesDeleteJob, CreatesMoveJob;

    protected function getAllJobs(Model $model): array
    {
        return collect($model->getUploadableAttributes())
            ->filter(fn ($type, $attribute) => $model->isDirty($attribute))
            ->flatMap(fn ($type, $attribute) => $this->createJobsFromAttribute($model, $attribute, $type)
            )
            ->filter()
            ->all();
    }

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
        return 'Handle uploads for modal update.';
    }
}
