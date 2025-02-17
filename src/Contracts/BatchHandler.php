<?php

namespace christopheraseidl\HasUploads\Contracts;

use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Model;
use Throwable;

interface BatchHandler
{
    public function dispatch(
        array $jobs,
        Model $model,
        string $disk,
        string $description
    ): void;

    public function handleSuccess(
        Batch $batch,
        Model $model,
        string $disk
    ): void;

    public function handleFailure(
        Batch $batch,
        Model $model,
        string $disk,
        Throwable $e
    ): void;
}
