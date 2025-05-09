<?php

namespace christopheraseidl\HasUploads\Handlers\Services;

use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker as ModelFileChangeTrackerContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ModelFileChangeTracker implements ModelFileChangeTrackerContract
{
    public array $originalPaths = [];

    public array $currentPaths = [];

    public function getNewFiles(Model $model, string $attribute): array
    {
        return array_values(
            array_diff(
                $this->getCurrentPaths($model, $attribute),
                $this->getOriginalPaths($model, $attribute)
            )
        );
    }

    public function getRemovedFiles(Model $model, string $attribute): array
    {
        return array_values(
            array_diff(
                $this->getOriginalPaths($model, $attribute),
                $this->getCurrentPaths($model, $attribute)
            )
        );
    }

    public function getCurrentPaths(Model $model, string $attribute): array
    {
        return $this->currentPaths[$attribute] ??= Arr::wrap($model->$attribute);
    }

    public function getOriginalPaths(Model $model, string $attribute): array
    {
        return $this->originalPaths[$attribute] ??= Arr::wrap($model->getOriginal($attribute));
    }
}
