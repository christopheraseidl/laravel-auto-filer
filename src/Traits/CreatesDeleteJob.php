<?php

namespace christopheraseidl\HasUploads\Traits;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\DeleteUploads;
use christopheraseidl\HasUploads\Payloads\DeleteUploadsPayload;
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
            ? $this->jobFactory->create(
                jobClass: DeleteUploads::class,
                payloadClass: DeleteUploadsPayload::class,
                args: [
                    $this->getClassBaseName($model),
                    $model->id,
                    $attribute,
                    $type,
                    OperationType::Delete,
                    OperationScope::File,
                    $this->disk,
                    $removedFiles,
                ]
            )
            : null;
    }
}
