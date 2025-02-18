<?php

namespace christopheraseidl\HasUploads\Traits;

use christopheraseidl\HasUploads\Contracts\JobBuilder;
use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\DeleteUploads;
use Illuminate\Database\Eloquent\Model;

trait CreatesDeleteJob
{
    protected function createDeleteJob(
        JobBuilder $builder,
        Model $model,
        string $attribute,
        ?string $type,
        string $disk,
        array $removedFiles,
    ): ?DeleteUploads {
        return ! empty($removedFiles)
            ? $builder
                ->job(DeleteUploads::class)
                ->modelClass(class_basename($model))
                ->modelId($model->id)
                ->modelAttribute($attribute)
                ->modelAttributeType($type)
                ->operationType(OperationType::Delete)
                ->operationScope(OperationScope::File)
                ->disk($disk)
                ->filePaths($removedFiles)
                ->build()
            : null;
    }
}
