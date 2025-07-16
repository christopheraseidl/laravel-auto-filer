<?php

namespace christopheraseidl\AutoFiler\Actions;

use christopheraseidl\AutoFiler\Contracts\GenerateThumbnail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class GenerateThumbnailAction implements GenerateThumbnail
{
    private readonly string $disk;

    public function __construct()
    {
        $this->disk = config('auto-filer.disk', 'public');
    }

    /**
     * Generate thumbnail for the given image path.
     */
    public function __invoke(string $imagePath, array $options = []): array
    {
        try {
            $width = $options['width'] ?? config('auto-filer.thumbnails.width', 400);
            $height = $options['height'] ?? config('auto-filer.thumbnails.height', null);
            $quality = $options['quality'] ?? config('auto-filer.thumbnails.quality', 85);
            $suffix = $options['suffix'] ?? config('auto-filer.thumbnails.suffix', '-thumb');

            // Read the image
            $image = Image::read(
                Storage::disk($this->disk)->get($imagePath)
            );

            // Resize maintaining aspect ratio
            $image->scale($width, $height);

            // Build thumbnail path
            $thumbPath = $this->buildThumbnailPath($imagePath, $suffix);

            // Save thumbnail
            Storage::disk($this->disk)->put(
                $thumbPath,
                $image->encodeByExtension(
                    pathinfo($imagePath, PATHINFO_EXTENSION),
                    quality: $quality
                )
            );

            return [
                'success' => true,
                'path' => $thumbPath,
            ];

        } catch (\Throwable $e) {
            Log::warning('Failed to generate thumbnail', [
                'image' => $imagePath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build the thumbnail path from the original image path.
     */
    private function buildThumbnailPath(string $imagePath, string $suffix): string
    {
        $info = pathinfo($imagePath);

        return $info['dirname'].'/'.
               $info['filename'].
               $suffix.
               '.'.$info['extension'];
    }
}
