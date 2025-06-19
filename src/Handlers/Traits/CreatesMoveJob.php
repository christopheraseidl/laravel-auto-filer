<?php

namespace christopheraseidl\ModelFiler\Handlers\Traits;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Jobs\Contracts\Builder;
use christopheraseidl\ModelFiler\Jobs\Contracts\MoveUploads;
use Illuminate\Database\Eloquent\Model;

/**
 * Creates move jobs for new file paths.
 */
trait CreatesMoveJob
{
    /**
     * Create a move job if new files exist, otherwise return null.
     */
    protected function createMoveJob(
        Builder $builder,
        Model $model,
        string $attribute,
        ?string $type,
        string $disk,
        array $newFiles
    ): ?MoveUploads {
        return ! empty($newFiles)
            ? $builder
                ->job(MoveUploads::class)
                ->modelClass($model::class)
                ->modelId($model->id)
                ->modelAttribute($attribute)
                ->modelAttributeType($type)
                ->operationType(OperationType::Move)
                ->operationScope(OperationScope::File)
                ->disk($disk)
                ->filePaths($newFiles)
                ->newDir($model->getUploadPath($type))
                ->build()
            : null;
    }
}
