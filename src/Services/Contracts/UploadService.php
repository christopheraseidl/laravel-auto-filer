<?php

namespace christopheraseidl\HasUploads\Services\Contracts;

use Illuminate\Http\UploadedFile;

/**
 * Manages file uploads with validation and storage operations.
 */
interface UploadService
{
    public function getDisk(): string;

    public function getPath(): string;

    public function validateUpload(UploadedFile $file): void;
}
