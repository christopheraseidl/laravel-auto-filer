<?php

namespace christopheraseidl\ModelFiler;

use christopheraseidl\ModelFiler\Handlers\ModelCreationHandler;
use christopheraseidl\ModelFiler\Handlers\ModelDeletionHandler;
use christopheraseidl\ModelFiler\Handlers\ModelUpdateHandler;
use christopheraseidl\ModelFiler\Services\Contracts\FileService;
use christopheraseidl\ModelFiler\Support\Contracts\UploadableAttributesBuilder as UploadableAttributesBuilderContract;
use christopheraseidl\ModelFiler\Support\UploadableAttributesBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Provides upload functionality to models with automatic file handling.
 */
trait HasFiles
{
    protected static ?FileService $fileService = null;

    public static function bootHasFiles(): void
    {
        static::$fileService = app(FileService::class);

        static::created(fn (Model $model) => app(ModelCreationHandler::class)->handle($model));
        static::saved(fn (Model $model) => app(ModelUpdateHandler::class)->handle($model));
        static::deleted(fn (Model $model) => app(ModelDeletionHandler::class)->handle($model));
    }

    /**
     * Define uploadable attributes and their corresponding asset types.
     *
     * Override in your model and use the fluent builder syntax:
     * return $this->uploadable('avatar')->as('images')->and('resume')->as('documents')->build();
     *
     * Or return a traditional array: ['avatar' => 'images', 'resume' => 'documents']
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
            $this->getFileService()->getPath(),
            $this->getModelDirName(),
            $this->id,
            $assetType,
        ]));
    }

    public function getModelDirName(): string
    {
        return Str::snake(Str::plural(class_basename($this)));
    }

    protected function getFileService(): FileService
    {
        return static::$fileService ??= app(FileService::class);
    }

    /**
     * Create a fluent builder for defining uploadable attributes.
     */
    protected function uploadable(string $attribute): UploadableAttributesBuilderContract
    {
        return (new UploadableAttributesBuilder)->uploadable($attribute);
    }

    protected function validateAssetType(string $assetType): void
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
