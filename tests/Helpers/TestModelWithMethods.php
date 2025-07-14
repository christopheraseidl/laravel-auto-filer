<?php

namespace christopheraseidl\ModelFiler\Tests\Helpers;

use christopheraseidl\ModelFiler\HasManagedFiles;
use Illuminate\Database\Eloquent\Model;

class TestModelWithMethods extends Model
{
    use HasManagedFiles;

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
