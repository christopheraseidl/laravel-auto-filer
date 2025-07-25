<?php

namespace christopheraseidl\AutoFiler\Tests\TestModels;

use christopheraseidl\AutoFiler\HasAutoFiles;
use Illuminate\Database\Eloquent\Model;

class TestModelWithMethods extends Model
{
    use HasAutoFiles;

    protected $table = 'test_models';

    public function files(): array
    {
        return ['resume' => 'documents', 'portfolio' => 'files'];
    }

    public function richTextFields(): array
    {
        return ['bio' => 'files'];
    }
}
