<?php

namespace christopheraseidl\ModelFiler\Tests\Helpers;

use christopheraseidl\ModelFiler\HasManagedFiles;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use HasManagedFiles;

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
