# A simple package for automating file upload storage and locations associated with models.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/christopheraseidl/laravel-model-filer.svg?style=flat-square)](https://packagist.org/packages/christopheraseidl/laravel-model-filer)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/christopheraseidl/laravel-model-filer/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/christopheraseidl/laravel-model-filer/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/christopheraseidl/laravel-model-filer/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/christopheraseidl/laravel-model-filer/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/christopheraseidl/laravel-model-filer.svg?style=flat-square)](https://packagist.org/packages/christopheraseidl/laravel-model-filer)

Laravel Model Filer is a simple package that automates file organization for your Eloquent models. It handles file uploads, moves them to organized directories, and automatically cleans up files when models are deleted.

## Installation

You can install the package via composer:

```bash
composer require christopheraseidl/laravel-model-filer
```

You can optionally publish the config file:

```bash
php artisan vendor:publish --tag="laravel-model-filer-config"
```

## Usage

Add the `HasFiles` trait to your model and define which attributes should handle file uploads:

```php
use christopheraseidl\ModelFiler\HasFiles;

class Product extends Model
{
    use HasFiles;

    protected $fillable = [
        'thumbnail',
        'images',
        'current_report',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
        ]
    }

    public function getUploadableAttributes(): array
    {
        return $this->uploadable('thumbnail')->as('images')
            ->and('images')->as('images')
            ->and('current_report')->as('documents')
            ->build();
    }
}
```
You can also just use an associative array with `getUploadableAttributes`:
```php
public function getUploadableAttributes(): array
    {
        return [
            'thumbnail' => 'images',
            'images' => 'images',
            'current_report' => 'documents'
        ];
    }
```

### How it works

1. **File Upload**: Upload files to a temporary location and store the paths in your model attributes.
2. **Model Save**: When the model is saved, files are automatically moved to their permanent location or deleted.
3. **Path Structure**: Files are organized as `disk://path/products/1/thumbnails/filename.jpg`.
4. **Model Delete**: When a model is deleted, all associated files are automatically removed.

### Example usage
```php
// Creating a new product with file uploads
$product = new Product([
    'name' => 'Awesome Product',
    'thumbnail' => 'temp/uploaded-thumbnail.jpg',
    'images' => [
        'temp/image1.jpg',
        'temp/image2.jpg',
    ],
    'current_report' => 'temp/current_report.pdf',
]);

$product->save();

// Files are now moved to:
// - products/1/thumbnails/uploaded-thumbnail.jpg
// - products/1/images/image1.jpg
// - products/1/images/image2.jpg
// - products/1/documents/current_report.pdf

// Updating files
$product->thumbnail = 'temp/new-thumbnail.jpg';
$product->save();
// Old thumbnail is deleted, new one is moved to products/1/thumbnails/new-thumbnail.jpg

// Deleting the model
$product->delete();
// All associated files are automatically deleted
```

### Scheduling a job to clean orphaned file uploads

You may schedule the `CleanOrphanedUploads` job by following [Laravel's documentation on scheduling jobs](https://laravel.com/docs/12.x/scheduling#scheduling-queued-jobs).

## Settings configuration

To customize settings, first publish the config file (see **Installation**) and then modify their values. By default, settings are as follows:

```php
return [
    // Storage disk to use
    'disk' => 'public',
    
    // Base path within the disk
    'path' => '',
    
    // Broadcast channel for real-time notifications
    'broadcast_channel' => 'default',
    
    // Maximum file size in KB
    'max_size' => 5120,
    
    // Allowed file extensions
    'mimes' => [
        'jpg', 'jpeg', 'png', 'ico',
        'pdf', 'doc', 'docx', 'txt',
    ],
    
    // Cleanup configuration
    'cleanup' => [
        'enabled' => false,
        'dry_run' => true,
        'threshold_hours' => 24,
    ],
    
    // Exception throttling
    'throttle_exception_attempts' => 10,
    'throttle_exception_period' => 10,
    
    // Circuit breaker configuration
    'circuit_breaker' => [
        'email_notifications' => false,
        'admin_email' => env('MAIL_FROM_ADDRESS'),
        'failure_threshold' => 5,
        'recovery_timeout' => 60,
        'half_open_attempts' => 3,
        'cache_ttl' => 1,
    ],
];
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Chris Seidl](https://github.com/christopheraseidl)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
