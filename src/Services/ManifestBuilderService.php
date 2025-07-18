<?php

namespace christopheraseidl\AutoFiler\Services;

use christopheraseidl\AutoFiler\Contracts\ManifestBuilder;
use christopheraseidl\AutoFiler\Contracts\RichTextScanner;
use christopheraseidl\AutoFiler\Exceptions\AutoFilerException;
use christopheraseidl\AutoFiler\ValueObjects\ChangeManifest;
use christopheraseidl\AutoFiler\ValueObjects\FileOperation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Builds a manifest of file operations to be processed.
 */
class ManifestBuilderService implements ManifestBuilder
{
    private readonly string $disk;

    private string $modelDir;

    private array $modelFileAttributes;

    private array $modelRichTextAttributes;

    public function __construct(
        private RichTextScanner $scanner
    ) {
        $this->disk = config('auto-filer.disk');
    }

    /**
     * Build a manifest of file operations to be processed.
     */
    public function buildManifest(Model $model, string $event): ChangeManifest
    {
        // Set model-based variables for use in manifest build
        $this->extractModelData($model);

        // Model deletion
        if ($this->isPermanentlyDeleted($model, $event)) {
            return new ChangeManifest(
                collect([FileOperation::delete($this->modelDir)])
            );
        }

        // Model creation/update
        $operations = collect($this->modelFileAttributes)
            ->merge(collect($this->modelRichTextAttributes))
            ->flatMap(
                fn ($subfolder, $attribute) => $this->buildFileOperations($model, $attribute)
            );

        return new ChangeManifest($operations);
    }

    /**
     * Determine whether manifest building should proceed.
     */
    public function shouldBuildManifest(Model $model, string $event): bool
    {
        return ! $this->isSoftDeleted($model, $event);
    }

    /**
     * Set the model attributes for use in manifest build
     */
    protected function extractModelData(Model $model): void
    {
        // Validate the model: it must use the HasAutoFiles trait.
        if (! method_exists($model, 'getModelDir') ||
            ! method_exists($model, 'getFileAttributes') ||
            ! method_exists($model, 'getRichTextAttributes')) {
            throw new AutoFilerException("The model {$model} must use the 'HasAutoFiles' trait.");
        }

        $this->modelDir = $model->getModelDir();
        $this->modelFileAttributes = $model->getFileAttributes();
        $this->modelRichTextAttributes = $model->getRichTextAttributes();
    }

    /**
     * Build operations for regular file attributes.
     */
    protected function buildFileOperations(Model $model, string $attribute): Collection
    {
        // Check if this is a rich text field
        if ($this->isRichTextField($model, $attribute)) {
            return $this->buildRichTextOperations($model, $attribute);
        }

        $current = collect(Arr::wrap($model->getAttribute($attribute)));
        $targetDir = $this->getFileDir($attribute);

        // If model was just created, all current files are new
        if ($model->wasRecentlyCreated) {
            return $current->map(
                fn ($file) => FileOperation::move(
                    $file,
                    $this->buildUniqueDestinationPath($file, $targetDir),
                    $model,
                    $attribute
                )
            );
        }

        // Otherwise, use diff logic for updates
        $original = collect(Arr::wrap($model->getOriginal($attribute)));

        return collect()
            ->merge(
                // New files to move
                $current->diff($original)->map(
                    fn ($file) => FileOperation::move(
                        $file,
                        $this->buildUniqueDestinationPath($file, $targetDir),
                        $model,
                        $attribute
                    )
                )
            )
            ->merge(
                // Old files to delete
                $original->diff($current)->map(
                    fn ($file) => FileOperation::delete($file)
                )
            );
    }

    /**
     * Build operations for rich text fields.
     */
    protected function buildRichTextOperations(Model $model, string $attribute): Collection
    {
        $currentContent = $model->getAttribute($attribute);
        $currentPaths = $this->scanner->extractPaths($currentContent);
        $targetDir = $this->getFileDir($attribute);

        // If model was just created, all current files are new
        if ($model->wasRecentlyCreated) {
            return $currentPaths->filter(function ($path) use ($targetDir) {
                // Filter out paths that already point to the target directory
                return ! $this->pointsToDestination($path, $targetDir);
            })->map(
                fn ($path) => FileOperation::move(
                    $path,
                    $this->buildUniqueDestinationPath($path, $targetDir),
                    $model,
                    $attribute
                )
            );
        }

        // For updates, compare original and current content
        $originalContent = $model->getOriginal($attribute);
        $originalPaths = $this->scanner->extractPaths($originalContent);

        return collect()
            ->merge(
                // New files to move (paths in current but not in original)
                $currentPaths->diff($originalPaths)
                    ->filter(function ($path) use ($targetDir) {
                        return ! $this->pointsToDestination($path, $targetDir);
                    })
                    ->map(function ($path) use ($targetDir, $model, $attribute) {
                        return FileOperation::moveRichText(
                            $path,
                            $this->buildUniqueDestinationPath($path, $targetDir),
                            $model,
                            $attribute
                        );
                    })
            )
            ->merge(
                // Old files to delete (paths in original but not in current)
                $originalPaths->diff($currentPaths)
                    ->filter(function ($path) use ($targetDir) {
                        // Only delete files that are in our target directory
                        return $this->pointsToDestination($path, $targetDir);
                    })
                    ->map(
                        fn ($path) => FileOperation::delete($path)
                    )
            );
    }

    /**
     * Get the file directory for the given model attribute
     */
    protected function getFileDir(string $attribute): string
    {
        return implode('/', array_filter([
            $this->modelDir,
            $this->modelFileAttributes[$attribute] ?? $this->modelRichTextAttributes[$attribute],
        ]));
    }

    /**
     * Build destination path from directory and source filename.
     */
    protected function buildUniqueDestinationPath(string $sourcePath, string $destinationDir): string
    {
        return $this->generateUniqueFileName(
            "{$destinationDir}/".pathinfo($sourcePath, PATHINFO_BASENAME)
        );
    }

    /**
     * Generate unique filename by appending counter if necessary.
     */
    protected function generateUniqueFileName(string $path): string
    {
        $info = pathinfo($path);
        $counter = 1;

        while ($this->fileExists($path)) {
            $path = $info['dirname'].'/'.$info['filename'].'_'.$counter.'.'.$info['extension'];
            $counter++;
        }

        return $path;
    }

    /**
     * Check if attribute contains rich text.
     */
    protected function isRichTextField(Model $model, string $attribute): bool
    {
        // Define in model via getRichTextAttributes() method
        return array_key_exists($attribute, $this->modelRichTextAttributes ?? []);
    }

    /**
     * Check file existence excluding zero-byte files.
     */
    protected function fileExists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path) && Storage::disk($this->disk)->size($path) > 0;
    }

    /**
     * Check to see if the model has been permanently deleted.
     */
    protected function isPermanentlyDeleted(Model $model, string $event): bool
    {
        return ($event === 'deleted' && ! $this->usesSoftDeletes($model))
            || $event === 'forceDeleted';
    }

    /**
     * Determine whether the model event is a soft deletion.
     */
    protected function isSoftDeleted(Model $model, string $event): bool
    {
        return $event === 'deleted' && $this->usesSoftDeletes($model);
    }

    /**
     * Check to see if the Model uses the SoftDeletes trait.
     */
    protected function usesSoftDeletes(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model));
    }

    /**
     * Check to see whether the path already points to the destination folder
     */
    protected function pointsToDestination(string $path, string $destination): bool
    {
        $pathArray = explode('/', $path);
        array_pop($pathArray);

        return implode('/', $pathArray) === $destination;
    }
}
