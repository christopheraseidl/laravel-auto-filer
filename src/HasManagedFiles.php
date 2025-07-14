<?php

namespace christopheraseidl\ModelFiler;

use christopheraseidl\ModelFiler\Observers\ModelObserver;
use Illuminate\Support\Str;

/**
 * Provides upload functionality to models with automatic file handling.
 */
trait HasManagedFiles
{
    /**
     * Cache for normalized file configuration.
     */
    protected ?array $normalizedFiles = null;

    /**
     * Cache for normalized rich text field configuration.
     */
    protected ?array $normalizedRichText = null;

    public static function bootOrganizesFiles(): void
    {
        static::observe(ModelObserver::class);
    }

    /**
     * Get file attributes from property or method.
     */
    public function getFileAttributes(): ?array
    {
        if ($this->normalizedFiles !== null) {
            return $this->normalizedFiles;
        }

        // Priority 1: Check for $file property
        if (property_exists($this, 'file')) {
            return $this->normalizedFiles = $this->normalizeConfig($this->file);
        }

        // Priority 2: Check if files() method exists
        if (method_exists($this, 'files')) {
            return $this->normalizedFiles = $this->normalizeConfig($this->files());
        }

        return $this->normalizedFiles = [];
    }

    /**
     * Get rich text attributes from property or method.
     */
    public function getRichTextAttributes(): ?array
    {
        if ($this->normalizedRichText !== null) {
            return $this->normalizedRichText;
        }

        // Priority 1: Check for $richText property
        if (property_exists($this, 'richText')) {
            return $this->normalizedRichText = $this->normalizeConfig($this->richText);
        }

        // Priority 2: Check if richTextFields() method exists
        if (method_exists($this, 'richTextFields')) {
            return $this->normalizedRichText = $this->normalizeConfig($this->richTextFields());
        }

        return $this->normalizedRichText = [];
    }

    public function getFileDir(string $attribute): string
    {
        $attributes = $this->getFileAttributes();
        $subfolder = $attributes[$attribute];

        return implode('/', array_filter([
            $this->getModelDir(),
            $subfolder,
        ]));
    }

    public function getModelDir(): string
    {
        return implode('/', array_filter([
            $this->getModelDirName(),
            $this->id,
        ]));
    }

    public function getModelDirName(): string
    {
        return Str::snake(Str::plural(class_basename($this)));
    }

    /**
     * Normalize various configuration formats into a consistent array.
     */
    protected function normalizeConfig(array $config): array
    {
        // Handle simple indexed array: ['avatar', 'resume']
        // Converts to: ['avatar' => 'files', 'resume' => 'files']
        if (array_is_list($config)) {
            return array_fill_keys($config, 'files');
        }

        // Handle associative array: ['avatar' => 'images', 'resume' => 'documents']
        return $config;
    }
}
