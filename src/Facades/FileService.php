<?php

namespace christopheraseidl\ModelFiler\Facades;

use Illuminate\Support\Facades\Facade;

class FileService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \christopheraseidl\ModelFiler\Services\FileService::class;
    }
}
