<?php

namespace christopheraseidl\HasUploads\Handlers;

use christopheraseidl\HasUploads\Traits\CreatesDeleteJob;
use christopheraseidl\HasUploads\Traits\CreatesMoveJob;
use Closure;
use Illuminate\Database\Eloquent\Model;

class HandleUploadsOnModelUpdate extends ModelUploadEventHandler
{
    use CreatesDeleteJob, CreatesMoveJob;

    protected function getAllJobs(Model $model, ?Closure $filter = null): array
    {
        return parent::getAllJobs(
            $model,
            $filter ?? fn ($model, $attribute) => $model->isDirty($attribute)
        );
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
