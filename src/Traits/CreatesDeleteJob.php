<?php

namespace christopheraseidl\HasUploads\Traits;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\DeleteUploads;
use Illuminate\Database\Eloquent\Model;

trait CreatesDeleteJob
{
    protected function createDeleteJob(
        Model $model,
        string $attribute,
        ?string $type,
        array $removedFiles
    ): ?DeleteUploads {
        return ! empty($removedFiles)
            ? $this->builder
                ->job(DeleteUploads::class)
                ->modelClass(class_basename($model))
                ->modelId($model->id)
                ->modelAttribute($attribute)
                ->modelAttributeType($type)
                ->operationType(OperationType::Delete)
                ->operationScope(OperationScope::File)
                ->disk($this->disk)
                ->filePaths($removedFiles)
                ->build()
            : null;
    }
}
