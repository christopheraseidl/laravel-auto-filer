<?php

namespace christopheraseidl\HasUploads\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ModelFileChangeTracker
{
    public function getRemovedFiles(Model $model, string $attribute): array;

    public function getNewFiles(Model $model, string $attribute): array;

    public function getOriginalPaths(Model $model, string $attribute): array;

    public function getCurrentPaths(Model $model, string $attribute): array;
}
