<?php

namespace christopheraseidl\HasUploads\Services;

use christopheraseidl\HasUploads\Services\Contracts\UploadService as UploadServiceContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Manages file uploads with validation and storage operations.
 */
class UploadService implements UploadServiceContract
{
    public function getDisk(): string
    {
        return config('has-uploads.disk', 'public');
    }

    public function getPath(): string
    {
        return config('has-uploads.path', '');
    }

    /**
     * Store uploaded file to model's path with validation.
     */
    public function storeFile(Model $model, UploadedFile $file, string $assetType = ''): string
    {
        $this->validateUpload($file);
        $path = $model->getUploadPath($assetType);
        $fileName = $file->hashName();
        Storage::disk($this->getDisk())->putFileAs($path, $file, $fileName);

        return "{$path}/{$fileName}";
    }

    /**
     * Validate file size and MIME type against configuration limits.
     */
    public function validateUpload(UploadedFile $file): void
    {
        $this->validateFileSize($file);
        $this->validateMimeType($file);
    }

    protected function validateFileSize(UploadedFile $file): void
    {
        if ($file->getSize() / 1024 > config('has-uploads.max_size')) {
            $maxSize = config('has-uploads.max_size');
            throw new \Exception("File size exceeds maximum allowed ({$maxSize}KB).");
        }
    }

    protected function validateMimeType(UploadedFile $file): void
    {
        if (! in_array($file->getClientOriginalExtension(), config('has-uploads.mimes'))) {
            throw new \Exception('Invalid file type.');
        }
    }
}
