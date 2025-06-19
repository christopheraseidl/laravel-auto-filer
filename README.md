# A simple package for automating file upload storage and locations associated with models.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/christopheraseidl/laravel-model-filer.svg?style=flat-square)](https://packagist.org/packages/christopheraseidl/laravel-model-filer)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/christopheraseidl/laravel-model-filer/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/christopheraseidl/laravel-model-filer/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/christopheraseidl/laravel-model-filer/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/christopheraseidl/laravel-model-filer/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/christopheraseidl/laravel-model-filer.svg?style=flat-square)](https://packagist.org/packages/christopheraseidl/laravel-model-filer)

The idea of this package is simple:

1. Add the `ModelFiler` trait to your model.
2. Set up your model and database for storing uploaded file paths (see **Usage** below).
3. Your files will be stored in logical, human-readable file paths unique for each model.

Files will be automatically deleted when their associated model is deleted. There is also a cleanup job that you can schedule called  `CleanOrphanedUploads`, which deletes orphaned uploaded files stored in the `uploads_tmp_path` directory (see **Usage**).

## Installation

You can install the package via composer:

```bash
composer require christopheraseidl/laravel-model-filer
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-model-filer-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-model-filer-config"
```

This is the contents of the published config file:

```php
return [
    'disk' => 'public',
    'path' => '',
    'max_size' => 5120,
    'mimes' => [
        'jpg',
        'jpeg',
        'png',
        'ico',
        'pdf',
        'doc',
        'docx',
        'txt',
    ],
    'cleanup' => [
        'temp_files' => [
            'enabled' => false,
            'threshold_hours' => 24,
        ],
        'orphaned_files' => [
            'enabled' => false,
            'threshold_days' => 7,
            'dry_run' => true,
            'backup' => false,
        ],
    ],
];
```

## Usage

To set up the model, add a `use` statement and the `getUploadableAttributes` method, which returns a key-value array where the keys are the attribute names and the values are the sub-directories where you want their uploaded files to be stored.

```php
use christopheraseidl\ModelFiler\HasFiles;

class Product extends Model
{
    use HasFiles;

    protected $fillable = [
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
        return [
            'images' => 'images',
            'current_report' => 'report',
        ];
    }
}
```

### Creating, updating, or deleting a model record

- Store the uploaded file path in the model attributes &mdash; in our example, `images` and `current_report`.
- The stored path values should be relative to the storage directory, for example `my-image.png`, which indicates that the file is stored in the root storage directory (default behavior). Upon saving the model, the path will be updated to something like `products/1/images/my-image.png`.
- The files will be copied to the same location as the path values.
- After the copied files are verified to exist in their new locations, the original uploaded files are automatically deleted.

### Scheduling a job to clean orphaned file uploads

You may schedule the `CleanOrphanedUploads` job by following [Laravel's documentation on scheduling jobs](https://laravel.com/docs/12.x/scheduling#scheduling-queued-jobs).

## Settings configuration

To customize settings, first publish the config file (see **Installation**) and then modify their values.

### disk

The name of the disk you want to use.

*default* : `'public'`

### uploads_tmp_path

The temporary uploads directory, relative to the storage root.

*default* : `''`

### final_path_prefix

The prefix path that you want to go before the models subdirectory.

*default* : `''`

### max_size

The maximum file syze, in kilobytes.

*default* : `'5120'`

### mimes

The permitted mime types.

*default* : `['jpg', 'jpeg', 'png', 'ico', 'pdf', 'doc', 'docx', 'txt']`

### cleanup

*default* :
```php
[
    'temp_files' => [
        'enabled' => false,
        'threshold_hours' => 24,
    ],
    'orphaned_files' => [
        'enabled' => false,
        'threshold_days' => 7,
        'dry_run' => true,
        'backup' => false,
    ],
],
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
