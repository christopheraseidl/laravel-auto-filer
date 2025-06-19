<?php

namespace christopheraseidl\ModelFiler\Services\Contracts;

use Illuminate\Http\UploadedFile;

/**
 * Manages file uploads with validation and storage operations.
 */
interface FileService
{
    public function getDisk(): string;

    public function getPath(): string;

    public function validateFile(UploadedFile $file): void;
}
