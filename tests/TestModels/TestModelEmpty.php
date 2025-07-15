<?php

namespace christopheraseidl\AutoFiler\Tests\TestModels;

use christopheraseidl\AutoFiler\HasAutoFiles;
use Illuminate\Database\Eloquent\Model;

class TestModelEmpty extends Model
{
    use HasAutoFiles;

    protected $table = 'test_models';
}
