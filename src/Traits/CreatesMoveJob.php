<?php

namespace christopheraseidl\HasUploads\Traits;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\MoveUploads;
use Illuminate\Database\Eloquent\Model;

trait CreatesMoveJob
{
    protected function createMoveJob(
        Model $model,
        string $attribute,
        ?string $type,
        array $newFiles
    ): ?MoveUploads {
        return ! empty($newFiles)
            ? $this->builder
                ->job(MoveUploads::class)
                ->modelClass(class_basename($model))
                ->modelId($model->id)
                ->modelAttribute($attribute)
                ->modelAttributeType($type)
                ->operationType(OperationType::Move)
                ->operationScope(OperationScope::File)
                ->disk($this->disk)
                ->filePaths($newFiles)
                ->newDir($model->getUploadPath($type))
                ->build()
            : null;
    }
}
