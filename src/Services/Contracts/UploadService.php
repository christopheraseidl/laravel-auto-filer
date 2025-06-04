<?php

namespace christopheraseidl\HasUploads\Services\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

interface UploadService
{
    public function getDisk(): string;

    public function getPath(): string;

    public function storeFile(Model $model, UploadedFile $file, string $assetType = ''): string;

    public function validateUpload(UploadedFile $file): void;
}
