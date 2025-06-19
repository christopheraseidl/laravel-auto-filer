<?php

namespace christopheraseidl\ModelFiler\Services;

use christopheraseidl\ModelFiler\Services\Contracts\FileService as FileServiceContract;
use Illuminate\Http\UploadedFile;

/**
 * Manages file uploads with validation and storage operations.
 */
class FileService implements FileServiceContract
{
    public function getDisk(): string
    {
        return config('model-filer.disk', 'public');
    }

    public function getPath(): string
    {
        return config('model-filer.path', '');
    }

    /**
     * Validate file size and MIME type against configuration limits.
     */
    public function validateFile(UploadedFile $file): void
    {
        $this->validateFileSize($file);
        $this->validateMimeType($file);
    }

    protected function validateFileSize(UploadedFile $file): void
    {
        if ($file->getSize() / 1024 > config('model-filer.max_size')) {
            $maxSize = config('model-filer.max_size');
            throw new \Exception("File size exceeds maximum allowed ({$maxSize}KB).");
        }
    }

    protected function validateMimeType(UploadedFile $file): void
    {
        if (! in_array($file->getClientOriginalExtension(), config('model-filer.mimes'))) {
            throw new \Exception('Invalid file type.');
        }
    }
}
