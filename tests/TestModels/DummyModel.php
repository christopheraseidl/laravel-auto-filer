<?php

namespace christopheraseidl\HasUploads\Tests\TestModels;

use christopheraseidl\HasUploads\HasUploads;
use Illuminate\Database\Eloquent\Model;

class DummyModel extends Model
{
    use HasUploads;
}
