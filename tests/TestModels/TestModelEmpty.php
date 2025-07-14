<?php

namespace christopheraseidl\ModelFiler\Tests\TestModels;

use christopheraseidl\ModelFiler\HasAutoFiles;
use Illuminate\Database\Eloquent\Model;

class TestModelEmpty extends Model
{
    use HasAutoFiles;

    protected $table = 'test_models';
}
