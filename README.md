# Automated file organization for Eloquent Models

[![Latest Version on Packagist](https://img.shields.io/packagist/v/christopheraseidl/laravel-auto-filer.svg?style=flat-square)](https://packagist.org/packages/christopheraseidl/laravel-auto-filer)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/christopheraseidl/laravel-auto-filer/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/christopheraseidl/laravel-auto-filer/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/christopheraseidl/laravel-auto-filer/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/christopheraseidl/laravel-auto-filer/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/christopheraseidl/laravel-auto-filer.svg?style=flat-square)](https://packagist.org/packages/christopheraseidl/laravel-auto-filer)

Laravel Auto Filer is a simple package that automates file organization for your Eloquent models. It handles file uploads, moves them to organized directories, and automatically cleans up files when they are deleted from the model or when their models are deleted.

### Key features

- **Automatic file organization**: files are organized in a predictable structure: `model_type/model_id/subfolder/filename`.
- **Automatic file movement**: files are automatically moved from temporary to permanent locations when models are saved.
- **Automatic cleanup**: files are deleted when their associated models are deleted or when the files have been removed from the model.
- **Rich text field support**: automatically handles files embedded in rich text content.
- **Thumbnail generation**: automatic thumbnail creation for images with automatic cleanup.
- **Circuit breaker protection**: built-in circuit breaker prevents cascading failures.
- **Laravel 10, 11, and 12 support**: compatible with recent Laravel versions.

## Installation

You can install the package via composer:

```bash
composer require christopheraseidl/laravel-auto-filer
```

You can optionally publish the config file:

```bash
php artisan vendor:publish --tag="laravel-auto-filer-config"
```

## Usage

Add the `HasAutoFiles` trait to your model and define which attributes should handle file uploads:

```php
use christopheraseidl\AutoFiler\HasAutoFiles;

class Product extends Model
{
    use HasAutoFiles;

    protected $fillable = [
        'images',
        'current_report',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
        ];
    }

    // Option 1: Using a property
    protected $file = [
        'images' => 'images',
        'current_report' => 'documents'
    ];

    // Option 2: Using a method
    public function files(): array
    {
        return [
            'images' => 'images',
            'current_report' => 'documents'
        ];
    }

    // Property for rich text fields
    protected $richText = [
        'description' => 'content',
    ];

    // Or using a method
    public function richTextFields(): array
    {
        return [
            'description' => 'content',
        ];
    }
}
```

### How it works

1. **File upload**: Upload files to a temporary location and store the paths in your model attributes.
2. **Model save**: When the model is saved, files are automatically moved to their permanent location.
3. **Path structure**: Files are organized as `disk://products/1/images/filename.jpg`.
4. **File deletion**: When a file is removed from a model attribute, it is deleted from the disk.
5. **Model delete**: When a model is deleted, all associated files and their thumbnails are automatically removed.
6. **Rich text processing**: Files referenced in rich text fields are automatically managed.
7. **Thumbnail management**: When enabled, thumbnails are automatically created for images and cleaned up when originals are removed.

### Example usage

```php
// Creating a new product with file uploads
$product = new Product([
    'name' => 'Awesome Product',
    'images' => [
        'uploads/temp/image1.jpg',
        'uploads/temp/image2.jpg',
    ],
    'current_report' => 'uploads/temp/current_report.pdf',
]);

$product->save();

// Files are now moved to:
// - products/1/images/image1.jpg (with thumbnail: image1-thumb.jpg if enabled)
// - products/1/images/image2.jpg (with thumbnail: image2-thumb.jpg if enabled)
// - products/1/documents/current_report.pdf

// Updating files
$product->images = ['uploads/temp/new-image.jpg'];
$product->save();
// Old images and their generated thumbnails are deleted, 
// new one is moved to products/1/images/new-image.jpg
// and a new thumbnail is automatically created if enabled

// Deleting the model
$product->delete();
// All associated files and their thumbnails are automatically deleted
```

### Thumbnail generation

The package automatically generates thumbnails for image files when enabled in the config. Thumbnails are created during file moves and automatically cleaned up when the original files are deleted.

When enabled, uploading an image will automatically create a thumbnail alongside the original:
- Original: `products/1/images/photo.jpg`
- Thumbnail: `products/1/images/photo-thumb.jpg`

Thumbnails are automatically managed: when you delete or replace the original image, the corresponding thumbnail is also removed.

### File Cleanup

You may schedule the `CleanOrphanedUploads` job by following [Laravel's documentation on scheduling jobs](https://laravel.com/docs/12.x/scheduling#scheduling-queued-jobs).

## Configuration

The package supports extensive configuration options. Here are the key settings:

```php
return [
    // Storage configuration
    'disk' => 'public',
    'temp_directory' => 'uploads/temp',
    
    // Queue configuration
    'queue_connection' => null,
    'queue' => null,
    'broadcast_channels' => null,
    
    // File validation
    'max_size' => 5120, // 5MB in KB
    'mimes' => [
        'image/jpeg',
        'image/png',
        'image/svg+xml',
        'application/pdf',
        'application/msword',
        'text/plain',
        'video/mp4',
        'audio/mpeg',
        // ... more MIME types
    ],
    'extensions' => [
        'jpg', 'jpeg', 'png', 'svg', 'ico',
        'pdf', 'doc', 'docx', 'odt', 'rtf',
        'txt', 'md', 'mp4', 'mp3',
    ],
    
    // Thumbnail generation
    'thumbnails' => [
        'enabled' => false,
        'width' => 400,
        'height' => null,
        'suffix' => '-thumb',
        'quality' => 85,
    ],
    
    // Cleanup configuration
    'cleanup' => [
        'enabled' => false,
        'dry_run' => true,
        'threshold_hours' => 24,
        'schedule' => 'daily',
    ],
    
    // Retry and throttling
    'maximum_file_operation_retries' => 3,
    'retry_wait_seconds' => 1,
    'throttle_exception_attempts' => 10,
    'throttle_exception_period' => 10, // minutes
];
```

## Testing

```bash
./vendor/bin/pest
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Chris Seidl](https://github.com/christopheraseidl)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.