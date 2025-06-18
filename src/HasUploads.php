<?php

namespace christopheraseidl\HasUploads;

use christopheraseidl\HasUploads\Handlers\ModelCreationHandler;
use christopheraseidl\HasUploads\Handlers\ModelDeletionHandler;
use christopheraseidl\HasUploads\Handlers\ModelUpdateHandler;
use christopheraseidl\HasUploads\Services\Contracts\UploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Provides upload functionality to models with automatic file handling.
 */
trait HasUploads
{
    protected static ?UploadService $uploadService = null;

    public static function bootHasUploads(): void
    {
        static::$uploadService = app(UploadService::class);

        static::created(fn (Model $model) => app(ModelCreationHandler::class)->handle($model));
        static::saved(fn (Model $model) => app(ModelUpdateHandler::class)->handle($model));
        static::deleted(fn (Model $model) => app(ModelDeletionHandler::class)->handle($model));
    }

    /**
     * Define uploadable attributes and their corresponding asset types.
     * Override in your model: ['avatar' => 'images', 'resume' => 'documents']
     */
    public function getUploadableAttributes(): array
    {
        return [];
    }

    public function getUploadPath(?string $assetType = null): string
    {
        if ($assetType) {
            $this->validateAssetType($assetType);
        }

        return implode('/', array_filter([
            $this->getUploadService()->getPath(),
            $this->getModelDirName(),
            $this->id,
            $assetType,
        ]));
    }

    public function getModelDirName(): string
    {
        return Str::snake(Str::plural(class_basename($this)));
    }

    protected function getUploadService(): UploadService
    {
        return static::$uploadService ??= app(UploadService::class);
    }

    private function validateAssetType(string $assetType): void
    {
        if (! $this->assetTypeExists($assetType)) {
            throw new \Exception("Asset type '{$assetType}' is not configured for ".static::class);
        }
    }

    private function assetTypeExists(string $assetType): bool
    {
        return in_array($this->sanitizePath($assetType), $this->getUploadableAttributes(), true);
    }

    private function sanitizePath(string $path): string
    {
        return basename(str_replace(['/', '\\'], '', $path));
    }
}
