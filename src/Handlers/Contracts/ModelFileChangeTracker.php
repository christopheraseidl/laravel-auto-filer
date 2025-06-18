<?php

namespace christopheraseidl\HasUploads\Handlers\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks file changes by comparing original and current model attribute values.
 */
interface ModelFileChangeTracker
{
    /**
     * Get files removed from the model attribute.
     */
    public function getRemovedFiles(Model $model, string $attribute): array;

    /**
     * Get new files added to the model attribute.
     */
    public function getNewFiles(Model $model, string $attribute): array;

    /**
     * Get original file paths before model changes.
     */
    public function getOriginalPaths(Model $model, string $attribute): array;

    /**
     * Get current file paths after model changes.
     */
    public function getCurrentPaths(Model $model, string $attribute): array;
}
