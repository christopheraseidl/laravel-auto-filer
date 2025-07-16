<?php

namespace christopheraseidl\AutoFiler\Tests\TestModels;

use Illuminate\Database\Eloquent\SoftDeletes;

class TestModelWithSoftDeletion extends TestModel
{
    use SoftDeletes;
}
