<?php

namespace christopheraseidl\HasUploads\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

interface UploadService
{
    public function getDisk(): string;

    public function getPath(): string;

    public function url(string $path): string;

    public function storeFile(Model $model, UploadedFile $file, string $assetType = ''): string;

    public function validateUpload(UploadedFile $file): void;

    public function moveFile(string $oldPath, string $newDir): string;

    public function deleteFile(string $path): bool;

    public function deleteDirectory(string $path): bool;
}
