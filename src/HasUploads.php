<?php

namespace christopheraseidl\HasUploads;

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Handlers\ModelCreationHandler;
use christopheraseidl\HasUploads\Handlers\ModelDeletionHandler;
use christopheraseidl\HasUploads\Handlers\ModelUpdateHandler;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUploads
{
    protected static ?UploadService $uploadService = null;

    public static function bootHasUploads()
    {
        static::$uploadService = app(UploadService::class);

        static::created(function (Model $model) {
            app(ModelCreationHandler::class)->handle($model);
        });

        static::saved(function (Model $model) {
            app(ModelUpdateHandler::class)->handle($model);
        });

        static::deleted(function (Model $model) {
            app(ModelDeletionHandler::class)->handle($model);
        });
    }

    protected function getUploadService(): UploadService
    {
        return static::$uploadService;
    }

    public function getUploadPath(?string $assetType = null): string
    {
        $this->validateAssetType($assetType);

        $parts = array_filter([
            $this->getUploadService()->getPath(),
            $this->getModelDirName(),
            $this->id,
            $assetType,
        ]);

        return implode('/', $parts);
    }

    private function validateAssetType(?string $assetType = null): void
    {
        if ($assetType && ! $this->assetTypeExists($assetType)) {
            throw new Exception("The asset type '$assetType' does not exist.");
        }
    }

    public function getModelDirName(): string
    {
        return Str::snake(Str::plural(class_basename($this)));
    }

    private function assetTypeExists(string $assetType): bool
    {
        $assetType = $this->sanitizePath($assetType);

        return array_search($assetType, $this->getUploadableAttributes(), true) !== false;
    }

    /**
     * Sanitize a path string to prevent directory traversal attacks.
     * Removes all directory separators and returns only the final path component.
     */
    private function sanitizePath(string $path): string
    {
        return basename(str_replace(['/', '\\'], '', $path));
    }

    /**
     * Override this method in your model to specify which attributes are uploadable (keys)
     * and where to put them (values).
     */
    public function getUploadableAttributes(): array
    {
        return [
            // 'attribute_name' => 'asset_type'
            // e.g., 'image' => 'images'
            // 'pdf' => 'documents'
        ];
    }
}
