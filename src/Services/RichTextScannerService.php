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
    public function extractPaths(?string $content): Collection
    {
        if (is_null($content)) {
            return collect();
        }

        $pattern = '/(src|href)\s*=\s*["\']([^"\']+(?:'.
                   implode('|', config('auto-filer.extensions')).
                   '))["\']?/i';

        preg_match_all($pattern, $content, $matches);

        return collect($matches[2] ?? [])
            ->filter(fn ($path) => $this->isManageablePath($path))
            ->map(fn ($path) => $this->normalizePath($path))
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
        return str_contains($path, $this->tempDirectory)
            && $this->isLocalPath($path);
    }

    /**
     * Normalize path to storage-relative format.
     */
    protected function normalizePath(string $path): string
    {
        $path = ltrim(
            parse_url($path, PHP_URL_PATH) ?? $path,
            '/'
        );

        $configPath = ltrim(
            $this->parseConfigUrl(config('app.url'))['path'] ?? '',
            '/'
        );

        if ($configPath && str_starts_with($path, $configPath)) {
            $path = substr($path, strlen($configPath) + 1);
        }

        return ltrim(
            preg_replace('#^/?storage/#', '', $path),
            '/'
        );
    }

    /**
     * Check if path is local (not external URL).
     */
    protected function isLocalPath(string $path): bool
    {
        // If it starts with // but has no scheme, add vestigial http: for validation
        $validationPath = str_starts_with($path, '//') ? "http:$path" : $path;

        // Return true for a relative path
        if (! filter_var($validationPath, FILTER_VALIDATE_URL)) {
            return true;
        }

        $parsedUrl = parse_url($validationPath);
        $parsedConfig = $this->parseConfigUrl(config('app.url'));

        // Get hosts from both URLs
        $urlHost = $parsedUrl['host'] ?? null;
        $configHost = $parsedConfig['host'] ?? null;

        // Return false if hosts are different
        if ($urlHost !== $configHost) {
            return false;
        }

        // Get paths from both URLs
        $urlPath = $parsedUrl['path'] ?? '/';
        $configPath = $parsedConfig['path'] ?? '/';

        // Ensure paths start with /
        $urlPath = '/'.ltrim($urlPath, '/');
        $configPath = '/'.ltrim($configPath, '/');

        // Check if the URL path starts with the config path
        return str_starts_with($urlPath, $configPath);
    }

    /**
     * Parse the config URL.
     */
    protected function parseConfigUrl(string $url): array
    {
        // Ensure config URL has a scheme for consistent parsing
        if (! preg_match('~^https?://~i', $url)) {
            $url = 'http://'.$url;
        }

        return parse_url($url);
    }
}
