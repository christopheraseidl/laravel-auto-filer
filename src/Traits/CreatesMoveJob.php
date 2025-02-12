<?php

namespace christopheraseidl\HasUploads\Traits;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\MoveUploads;
use christopheraseidl\HasUploads\Payloads\MoveUploadsPayload;
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
            ? $this->jobFactory->create(
                jobClass: MoveUploads::class,
                payloadClass: MoveUploadsPayload::class,
                args: [
                    $this->getClassBaseName($model),
                    $model->id,
                    $attribute,
                    $type,
                    OperationType::Move,
                    OperationScope::File,
                    $this->disk,
                    $newFiles,
                    $model->getUploadPath($type),
                ]
            )
            : null;
    }
}
