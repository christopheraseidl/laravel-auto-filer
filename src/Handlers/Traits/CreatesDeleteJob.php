<?php

namespace christopheraseidl\ModelFiler\Handlers\Traits;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Jobs\Contracts\Builder;
use christopheraseidl\ModelFiler\Jobs\Contracts\DeleteUploads;
use Illuminate\Database\Eloquent\Model;

/**
 * Creates delete jobs for removed file paths.
 */
trait CreatesDeleteJob
{
    /**
     * Create a delete job if removed files exist, otherwise return null.
     */
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
                ->modelClass($model::class)
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
