<?php

namespace christopheraseidl\ModelFiler\Tests\Helpers;

use christopheraseidl\ModelFiler\HasManagedFiles;
use Illuminate\Database\Eloquent\Model;

class TestModelEmpty extends Model
{
    use HasManagedFiles;

    protected $table = 'test_models';
}
