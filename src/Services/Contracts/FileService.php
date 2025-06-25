<?php

namespace christopheraseidl\ModelFiler\Services\Contracts;

use Illuminate\Http\UploadedFile;

/**
 * Manages file uploads with validation and storage operations.
 */
interface FileService
{
    /**
     * Return storage disk name.
     */
    public function getDisk(): string;

    /**
     * Get the file prefix path.
     */
    public function getPath(): string;

    /**
     * Validate file size and MIME type against configuration limits.
     */
    public function validateFile(UploadedFile $file): void;
}
