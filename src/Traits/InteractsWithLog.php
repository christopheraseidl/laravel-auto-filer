<?php

namespace christopheraseidl\ModelFiler\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Implements logging methods using Laravel's Log facade.
 */
trait InteractsWithLog
{
    /**
     * Log info message.
     */
    public function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    /**
     * Log warning message.
     */
    public function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    /**
     * Log error message.
     */
    public function logError(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }
}
