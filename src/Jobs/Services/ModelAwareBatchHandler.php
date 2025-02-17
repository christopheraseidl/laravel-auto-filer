<?php

namespace christopheraseidl\HasUploads\Jobs\Services;

use christopheraseidl\HasUploads\Contracts\ModelAwareBatchHandler as ModelAwareBatchHandlerContract;
use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use christopheraseidl\HasUploads\Payloads\BatchUpdatePayload;
use christopheraseidl\HasUploads\Traits\GetsClassBaseName;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Throwable;

class ModelAwareBatchHandler implements ModelAwareBatchHandlerContract
{
    use GetsClassBaseName;

    public function dispatch(
        array $jobs,
        Model $model,
        string $disk,
        string $description
    ): void {
        Bus::batch($jobs)
            ->name($description)
            ->then(fn (Batch $batch) => $this->handleSuccess($batch, $model, $disk))
            ->catch(fn (Batch $batch, Throwable $e) => $this->handleFailure($batch, $model, $disk, $e))
            ->dispatch();
    }

    public function handleSuccess(
        Batch $batch,
        Model $model,
        string $disk
    ): void {
        broadcast(new FileOperationCompleted(
            BatchUpdatePayload::make(
                modelClass: $this->getClassBaseName($model),
                modelId: $model->id,
                operationType: OperationType::Update,
                operationScope: OperationScope::Batch,
                disk: $disk
            )
        ));
    }

    public function handleFailure(
        Batch $batch,
        Model $model,
        string $disk,
        Throwable $e
    ): void {
        broadcast(new FileOperationFailed(
            BatchUpdatePayload::make(
                modelClass: $this->getClassBaseName($model),
                modelId: $model->id,
                operationType: OperationType::Update,
                operationScope: OperationScope::Batch,
                disk: $disk
            ),
            $e
        ));
    }
}
