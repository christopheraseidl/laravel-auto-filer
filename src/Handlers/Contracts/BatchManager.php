<?php

namespace christopheraseidl\ModelFiler\Handlers\Contracts;

use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Manages batch job dispatch and broadcasts completion events.
 */
interface BatchManager
{
    /**
     * Dispatch a batch of jobs for the given model and disk.
     */
    public function dispatch(
        array $jobs,
        Model $model,
        string $disk,
        string $description
    ): void;

    /**
     * Handle successful completion of a batch operation.
     */
    public function handleSuccess(
        Batch $batch,
        Model $model,
        string $disk
    ): void;

    /**
     * Handle failure of a batch operation.
     */
    public function handleFailure(
        Batch $batch,
        Model $model,
        string $disk,
        Throwable $e
    ): void;
}
