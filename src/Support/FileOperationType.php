<?php

namespace christopheraseidl\ModelFiler\Support;

use christopheraseidl\ModelFiler\Contracts\FileOperationTypeFormatter;
use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;

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
