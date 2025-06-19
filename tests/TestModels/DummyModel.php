<?php

namespace christopheraseidl\ModelFiler\Tests\TestModels;

use christopheraseidl\ModelFiler\HasFiles;
use Illuminate\Database\Eloquent\Model;

class DummyModel extends Model
{
    use HasFiles;
}
