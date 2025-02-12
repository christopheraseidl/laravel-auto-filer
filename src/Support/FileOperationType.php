<?php

namespace christopheraseidl\HasUploads\Support;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;

class FileOperationType
{
    public static function get(OperationType $type, OperationScope $scope): string
    {
        return sprintf('%s_%s', str_replace(' ', '_', $type->value), $scope->value);
    }
}
