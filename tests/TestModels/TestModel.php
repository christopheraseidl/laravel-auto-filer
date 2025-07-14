<?php

namespace christopheraseidl\ModelFiler\Tests\TestModels;

use christopheraseidl\ModelFiler\HasAutoFiles;
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
}
