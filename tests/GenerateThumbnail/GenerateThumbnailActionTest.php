<?php

namespace christopheraseidl\AutoFiler\Tests\Actions;

use christopheraseidl\AutoFiler\Actions\GenerateThumbnailAction;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    config()->set('auto-filer.disk', 'public');
    config()->set('auto-filer.thumbnails.width', 400);
    config()->set('auto-filer.thumbnails.height', null);
    config()->set('auto-filer.thumbnails.quality', 85);
    config()->set('auto-filer.thumbnails.suffix', '-thumb');

    $this->action = new GenerateThumbnailAction;
});

it('generates thumbnail with default configuration', function () {
    // Create a simple test image
    $image = imagecreatetruecolor(100, 100);
    ob_start();
    imagejpeg($image);
    $imageContent = ob_get_clean();
    imagedestroy($image);

    Storage::disk('public')->put('images/photo.jpg', $imageContent);

    $result = ($this->action)('images/photo.jpg');

    expect($result)->toMatchArray([
        'success' => true,
        'path' => 'images/photo-thumb.jpg',
    ]);
    expect(Storage::disk('public')->exists('images/photo-thumb.jpg'))->toBeTrue();
});

it('generates thumbnail with custom options', function () {
    $image = imagecreatetruecolor(100, 100);
    ob_start();
    imagepng($image);
    $imageContent = ob_get_clean();
    imagedestroy($image);

    Storage::disk('public')->put('photos/image.png', $imageContent);

    $result = ($this->action)('photos/image.png', [
        'width' => 200,
        'height' => 150,
        'quality' => 90,
        'suffix' => '_small',
    ]);

    expect($result)->toMatchArray([
        'success' => true,
        'path' => 'photos/image_small.png',
    ]);
    expect(Storage::disk('public')->exists('photos/image_small.png'))->toBeTrue();
});

it('uses configured disk', function () {
    config()->set('auto-filer.disk', 'local');
    Storage::fake('local');

    $image = imagecreatetruecolor(100, 100);
    ob_start();
    imagejpeg($image);
    $imageContent = ob_get_clean();
    imagedestroy($image);

    Storage::disk('local')->put('test.jpg', $imageContent);

    $action = new GenerateThumbnailAction;

    $result = ($action)('test.jpg');

    expect($result['success'])->toBeTrue();
    expect(Storage::disk('local')->exists('test-thumb.jpg'))->toBeTrue();
});

it('handles nested directory paths', function () {
    $image = imagecreatetruecolor(100, 100);
    ob_start();
    imagejpeg($image);
    $imageContent = ob_get_clean();
    imagedestroy($image);

    Storage::disk('public')->put('uploads/2024/01/photo.jpg', $imageContent);

    $result = ($this->action)('uploads/2024/01/photo.jpg');

    expect($result['path'])->toBe('uploads/2024/01/photo-thumb.jpg');
    expect(Storage::disk('public')->exists('uploads/2024/01/photo-thumb.jpg'))->toBeTrue();
});

it('preserves file extensions', function ($ext) {
    $image = imagecreatetruecolor(100, 100);
    ob_start();

    match ($ext) {
        'jpg', 'jpeg' => imagejpeg($image),
        'png' => imagepng($image),
        'gif' => imagegif($image),
        'webp' => imagewebp($image),
    };

    $imageContent = ob_get_clean();
    imagedestroy($image);

    Storage::disk('public')->put("image.{$ext}", $imageContent);

    $result = ($this->action)("image.{$ext}");

    expect($result['path'])->toBe("./image-thumb.{$ext}");
    expect(Storage::disk('public')->exists("./image-thumb.{$ext}"))->toBeTrue();
})->with(['jpg', 'jpeg', 'png', 'gif', 'webp']);

it('returns error when processing invalid image data', function () {
    Storage::disk('public')->put('broken.jpg', 'invalid-image-data');

    $result = ($this->action)('broken.jpg');

    expect($result['success'])->toBeFalse();
    expect($result)->toHaveKey('error');
});

it('returns error when file does not exist', function () {
    $result = ($this->action)('non-existent.jpg');

    expect($result['success'])->toBeFalse();
    expect($result)->toHaveKey('error');
});

it('handles various image sizes', function () {
    // Test with different image dimensions
    $sizes = [[50, 50], [500, 300], [1000, 2000]];

    foreach ($sizes as [$width, $height]) {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagejpeg($image);
        $imageContent = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put("test-{$width}x{$height}.jpg", $imageContent);

        $result = ($this->action)("test-{$width}x{$height}.jpg");

        expect($result['success'])->toBeTrue();
        expect(Storage::disk('public')->exists("test-{$width}x{$height}-thumb.jpg"))->toBeTrue();
    }
});
