<?php

namespace christopheraseidl\HasUploads\Handlers\Services;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Events\FileOperationCompleted;
use christopheraseidl\HasUploads\Events\FileOperationFailed;
use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager as BatchManagerContract;
use christopheraseidl\HasUploads\Payloads\BatchUpdate;
use christopheraseidl\HasUploads\Payloads\Contracts\Payload;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;

/**
 * Manages batch job dispatch and broadcasts completion events.
 */
class BatchManager implements BatchManagerContract
{
    public function dispatch(
        array $jobs,
        Model $model,
        string $disk,
        string $description
    ): void {
        Bus::batch($jobs)
            ->name($description)
            ->then(fn (Batch $batch) => $this->handleSuccess($batch, $model, $disk))
            ->catch(fn (Batch $batch, \Throwable $e) => $this->handleFailure($batch, $model, $disk, $e))
            ->dispatch();
    }

    public function handleSuccess(
        Batch $batch,
        Model $model,
        string $disk
    ): void {
        broadcast(new FileOperationCompleted(
            $this->makeFileOperationPayload($batch, $model, $disk)
        ));
    }

    public function handleFailure(
        Batch $batch,
        Model $model,
        string $disk,
        \Throwable $e
    ): void {
        broadcast(new FileOperationFailed(
            $this->makeFileOperationPayload($batch, $model, $disk),
            $e
        ));
    }

    /**
     * Create a batch update payload for broadcasting.
     */
    private function makeFileOperationPayload(
        Batch $batch,
        Model $model,
        string $disk
    ): Payload {
        return BatchUpdate::make(
            modelClass: class_basename($model),
            modelId: $model->id,
            operationType: OperationType::Update,
            operationScope: OperationScope::Batch,
            disk: $disk
        );
    }
}
