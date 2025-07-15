<?php

namespace christopheraseidl\AutoFiler\Services;

use christopheraseidl\AutoFiler\Contracts\RichTextScanner;
use Illuminate\Support\Collection;

class RichTextScannerService implements RichTextScanner
{
    private readonly string $tempDirectory;

    public function __construct()
    {
        $this->tempDirectory = config('auto-filer.temp_directory');
    }

    /**
     * Extract file paths from rich text content.
     */
    public function extractPaths(string $content): Collection
    {
        $pattern = '/(src|href)=["\']([^"\']+(?:'.
                   implode('|', config('auto-filer.extensions')).
                   '))["\']?/i';

        preg_match_all($pattern, $content, $matches);

        return collect($matches[2] ?? [])
            ->map(fn ($path) => $this->normalizePath($path))
            ->filter(fn ($path) => $this->isManageablePath($path))
            ->unique()
            ->values();
    }

    /**
     * Replace file paths in content with new paths.
     */
    public function updatePaths(string $content, array $replacements): string
    {
        foreach ($replacements as $old => $new) {
            $content = str_replace($old, $new, $content);
        }

        return $content;
    }

    /**
     * Check if path should be managed by this package.
     */
    public function isManageablePath(string $path): bool
    {
        // Only manage files from temp directory
        return str_contains($path, $this->tempDirectory) &&
               $this->isLocalPath($path);
    }

    /**
     * Normalize path to storage-relative format.
     */
    private function normalizePath(string $path): string
    {
        // Remove domain and storage prefixes
        $path = parse_url($path, PHP_URL_PATH) ?? $path;
        $path = preg_replace('#^/storage/#', '', $path);

        return ltrim($path, '/');
    }

    /**
     * Check if path is local (not external URL).
     */
    private function isLocalPath(string $path): bool
    {
        return ! filter_var($path, FILTER_VALIDATE_URL) ||
               str_contains($path, config('app.url'));
    }
}
