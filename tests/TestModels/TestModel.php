<?php

namespace christopheraseidl\AutoFiler\Tests\TestModels;

use christopheraseidl\AutoFiler\HasAutoFiles;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use HasAutoFiles;

    protected $table = 'test_models';

    protected $fillable = ['id', 'name', 'avatar', 'documents', 'description'];

    // Custom file configuration: avatar as string, documents as array
    protected $file = ['avatar' => 'images', 'documents' => 'files'];

    // Rich text configuration
    protected $richText = ['description'];

    protected $casts = [
        'documents' => 'array',
    ];

    // Enable public access to normalizedFiles array for testing
    public function getNormalizedFiles(): ?array
    {
        return $this->normalizedFiles;
    }

    // Enable public access to normalizedRichText array for testing
    public function getNormalizedRichText(): ?array
    {
        return $this->normalizedRichText;
    }

    // Enable public access to normalizeConfig() for testing
    public function getNormalizedConfig(array $config): array
    {
        return $this->normalizeConfig($config);
    }
}
