<?php

namespace christopheraseidl\ModelFiler\Tests\TestModels;

use christopheraseidl\ModelFiler\HasFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use HasFactory, HasFiles;

    protected $table = 'test_models';

    protected $fillable = ['string', 'array'];

    protected $casts = ['array' => 'array'];

    protected function casts(): array
    {
        return [
            'array' => 'array',
        ];
    }

    protected static function newFactory()
    {
        return TestModelFactory::new();
    }

    public function getUploadableAttributes(): array
    {
        return [
            'string' => 'images',
            'array' => 'documents',
        ];
    }

    public function getArray(): array
    {
        return $this->array;
    }
}
