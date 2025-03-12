<?php

namespace christopheraseidl\HasUploads\Handlers\Traits;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\Contracts\Builder;
use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploads;
use Illuminate\Database\Eloquent\Model;

trait CreatesDeleteJob
{
    protected function createDeleteJob(
        Builder $builder,
        Model $model,
        string $attribute,
        ?string $type,
        string $disk,
        array $removedFiles,
    ): ?DeleteUploads {
        return ! empty($removedFiles)
            ? $builder
                ->job(DeleteUploads::class)
                ->modelClass(get_class($model))
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
