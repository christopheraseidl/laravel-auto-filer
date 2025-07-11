<?php

namespace christopheraseidl\ModelFiler\Services;

use christopheraseidl\ModelFiler\Contracts\ManifestBuilder;
use christopheraseidl\ModelFiler\Contracts\RichTextScanner;
use christopheraseidl\ModelFiler\ValueObjects\ChangeManifest;
use christopheraseidl\ModelFiler\ValueObjects\FileOperation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Builds a manifest of file operations to be processed.
 */
class ManifestBuilderService implements ManifestBuilder
{
    public function __construct(
        private RichTextScanner $scanner
    ) {}

    /**
     * Build a manifest of file operations to be processed.
     */
    public function buildManifest(Model $model, string $event): ChangeManifest
    {
        // Model deletion
        if ($this->isPermanentlyDeleted($event)) {
            return new ChangeManifest(
                collect([FileOperation::delete($model->getModelDir())])
            );
        }

        // Model creation/update
        $operations = collect($model->getFileAttributes())->flatMap(
            fn($attribute, $subfolder) => $this->buildFileOperations($model, $attribute, $subfolder)
        );

        return new ChangeManifest($operations);
    }

    protected function buildFileOperations(Model $model, string $attribute, string $subfolder): Collection
    {
        // Check if this is a rich text field
        if ($this->isRichTextField($model, $attribute)) {
            return $this->buildRichTextOperations($model, $attribute, $subfolder);
        }

        $current = collect(Arr::wrap($model->getAttribute($attribute)));
        $original = collect(Arr::wrap($model->getOriginal($attribute)));
        $targetDir = $model->getFileDir($subfolder);

        return collect()
            ->merge(
                // New files to move
                $current->diff($original)->map(
                    fn($file) => FileOperation::move(
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
                   fn($file) => FileOperation::delete($file)
                )
            );
    }

    /**
     * Build operations for rich text fields.
     */
    private function buildRichTextOperations(Model $model, string $attribute, string $subfolder): Collection
    {
        $content = $model->getAttribute($attribute);
        $paths = $this->scanner->extractPaths($content);
        $targetDir = $model->getFileDir($subfolder);
        
        return $paths->map(function ($path) use ($targetDir, $model, $attribute) {
            $destination = $this->buildUniqueDestinationPath($path, $targetDir);
            
            return FileOperation::moveRichText(
                $path,
                $destination,
                $model,
                $attribute
            );
        });
    }

    /**
     * Build destination path from directory and source filename.
     */
    private function buildUniqueDestinationPath(string $sourcePath, string $destinationDir): string
    {
        return $this->generateUniqueFileName(
            "{$destinationDir}/".pathinfo($sourcePath, PATHINFO_BASENAME)
        );
    }

    /**
     * Generate unique filename by appending counter if necessary.
     */
    private function generateUniqueFileName(string $path): string
    {
        $info = pathinfo($path);
        $counter = 1;

        while ($this->fileExists($path)) {
            $path = $info['dirname'].'/'.$info['filename'].'_'.$counter.'.'.$info['extension'];
            $counter++;
        }

        return $path;
    }

    private function isPermanentlyDeleted($event): bool
    {
        return in_array($event, ['deleted', 'forceDeleted']);
    }
}
