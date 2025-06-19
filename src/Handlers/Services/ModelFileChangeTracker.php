<?php

namespace christopheraseidl\ModelFiler\Handlers\Services;

use christopheraseidl\ModelFiler\Handlers\Contracts\ModelFileChangeTracker as ModelFileChangeTrackerContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Tracks file changes by comparing original and current model attribute values.
 */
class ModelFileChangeTracker implements ModelFileChangeTrackerContract
{
    public array $originalPaths = [];

    public array $currentPaths = [];

    /**
     * Get files present in current but not in original paths.
     */
    public function getNewFiles(Model $model, string $attribute): array
    {
        return array_values(
            array_diff(
                $this->getCurrentPaths($model, $attribute),
                $this->getOriginalPaths($model, $attribute)
            )
        );
    }

    /**
     * Get files present in original but not in current paths.
     */
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
