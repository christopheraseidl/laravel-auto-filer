<?php

namespace christopheraseidl\ModelFiler\Contracts;

use Illuminate\Support\Collection;

interface RichTextScanner
{
    public function __construct(?string $tempDirectory);

    /**
     * Extract file paths from rich text content.
     */
    public function extractPaths(string $content): Collection;

    /**
     * Replace file paths in content with new paths.
     */
    public function updatePaths(string $content, array $replacements): string;

    /**
     * Check if path should be managed by this package.
     */
    public function isManageablePath(string $path): bool;
}