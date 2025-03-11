<?php

namespace christopheraseidl\HasUploads\Jobs\Contracts;

use christopheraseidl\HasUploads\Payloads\Contracts\MoveUploads as MoveUploadsPayload;
use Illuminate\Database\Eloquent\Model;

interface MoveUploads extends Job
{
    public function __construct(MoveUploadsPayload $payload);

    public function attemptMove(string $disk, string $oldPath, string $newDir): string;

    public function normalizeAttributeValue(Model $model, string $attribute): string|array|null;
}
