<?php

namespace christopheraseidl\ModelFiler\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Implements logging methods using Laravel's Log facade.
 */
trait InteractsWithLog
{
    /**
     * Resolve intercoming method calls as Log facade methods.
     */
    public function __call(string $method, array $arguments): self
    {
        $prefix = 'log';
        $method = str_replace($prefix, '', $method);

        $this->validateMacro($method, $arguments);

        return Log::$method(...$arguments);
    }

    /**
     * Validate method existence on the Laravel Log facade.
     */
    public function validateMacro(string $method, array $arguments): void
    {
        if (! $this->logMethodExists($method)) {
            $message = "Method '{$method}' does not exist on the Log facade";

            throw new \BadMethodCallException($message);
        }
    }

    /**
     * Check if method exists on Log facade (either regular method or macro).
     */
    private function logMethodExists(string $method): bool
    {
        if (Log::hasMacro($method)) {
            return true;
        }

        $logger = Log::getFacadeRoot();

        return method_exists($logger, $method);
    }
}
