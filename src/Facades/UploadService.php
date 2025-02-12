<?php

namespace christopheraseidl\HasUploads\Facades;

use Illuminate\Support\Facades\Facade;

class UploadService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \christopheraseidl\HasUploads\Support\UploadService::class;
    }
}
