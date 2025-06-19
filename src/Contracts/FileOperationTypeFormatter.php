<?php

namespace christopheraseidl\ModelFiler\Contracts;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;

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
