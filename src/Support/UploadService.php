<?php

namespace christopheraseidl\HasUploads\Support;

use christopheraseidl\HasUploads\Contracts\UploadService as UploadServiceContract;
use christopheraseidl\HasUploads\Traits\AttemptsFileMoves;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadService implements UploadServiceContract
{
    use AttemptsFileMoves;

    public function getDisk(): string
    {
        return config('has-uploads.disk', 'public');
    }

    public function getPath(): string
    {
        return config('has-uploads.path', '');
    }

    public function url(string $path): string
    {
        return Storage::disk($this->getDisk())->url($path);
    }

    public function storeFile(Model $model, UploadedFile $file, string $assetType = ''): string
    {
        $this->validateUpload($file);
        $path = $model->getUploadPath($assetType);
        $fileName = $file->hashName();
        Storage::disk(UploadService::getDisk())->putFileAs($path, $file, $fileName);

        return "{$path}/{$fileName}";
    }

    public function validateUpload(UploadedFile $file): void
    {
        if ($file->getSize() / 1024 > config('has-uploads.max_size')) {
            throw new Exception("File size exceeds maximum allowed ({config('has-uploads.max_size')}KB).");
        }

        if (! in_array($file->getClientOriginalExtension(), config('has-uploads.mimes'))) {
            throw new Exception('Invalid file type.');
        }
    }

    public function moveFile(string $oldPath, string $newDir): string
    {
        return $this->attemptMove($this->getDisk(), $oldPath, $newDir);
    }

    public function deleteFile(string $path): bool
    {
        return Storage::disk($this->getDisk())->delete($path);
    }

    public function deleteDirectory(string $path): bool
    {
        return Storage::disk($this->getDisk())
            ->deleteDirectory($path);
    }
}
