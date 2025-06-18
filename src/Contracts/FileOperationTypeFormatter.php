<?php

namespace christopheraseidl\HasUploads\Contracts;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;

/**
 * Formats operation type and scope combinations into string identifiers.
 */
interface FileOperationTypeFormatter
{
    /**
     * Generate formatted string combining operation type and scope.
     */
    public static function get(OperationType $type, OperationScope $scope): string;
}
