<?php

namespace christopheraseidl\HasUploads\Support;

use christopheraseidl\HasUploads\Contracts\FileOperationTypeFormatter;
use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;

/**
 * Formats operation type and scope combinations into string identifiers.
 */
class FileOperationType implements FileOperationTypeFormatter
{
    /**
     * Generate formatted string combining operation type and scope.
     */
    public static function get(OperationType $type, OperationScope $scope): string
    {
        return sprintf('%s_%s', str_replace(' ', '_', $type->value), $scope->value);
    }
}
