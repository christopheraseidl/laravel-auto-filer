<?php

namespace christopheraseidl\ModelFiler\Jobs\Contracts;

use christopheraseidl\ModelFiler\Contracts\MovesFiles;
use christopheraseidl\ModelFiler\Payloads\Contracts\MoveUploads as MoveUploadsPayload;
use Illuminate\Database\Eloquent\Model;

/**
 * Deletes a specific uploaded file.
 */
interface MoveUploads extends Job, MovesFiles
{
    public function __construct(MoveUploadsPayload $payload);

    /**
     * Normalize attribute value based on model casting configuration.
     */
    public function normalizeAttributeValue(Model $model, string $attribute): string|array|null;

    /**
     * Get the array difference, remove empty values, and reindex result.
     */
    public function arrayDiff(array $array1, array $array2): array;

    /**
     * Merge arrays, remove empty values, and reindex result.
     */
    public function arrayMerge(array $array1, array $array2): array;
}
