<?php

namespace christopheraseidl\ModelFiler\Contracts;

/**
 * Provides logging capabilities for implementing classes.
 */
interface Loggable
{
    /**
     * Log info message.
     */
    public function logInfo(string $message, array $context = []): void;

    /**
     * Log warning message.
     */
    public function logWarning(string $message, array $context = []): void;

    /**
     * Log error message.
     */
    public function logError(string $message, array $context = []): void;
}
