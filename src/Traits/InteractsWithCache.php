<?php

namespace christopheraseidl\ModelFiler\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Implements caching methods using Laravel's Cache facade.
 */
trait InteractsWithCache
{
    /**
     * Resolve intercoming method calls as Cache facade methods.
     */
    public function __call(string $method, array $arguments): self
    {
        $prefix = 'cache';
        $method = str_replace($prefix, '', $method);

        $this->validateMacro($method, $arguments);

        return Cache::$method(...$arguments);
    }

    /**
     * Validate method existence on the Laravel Cache facade.
     */
    public function validateMacro(string $method, array $arguments): void
    {
        if (! $this->cacheMethodExists($method)) {
            $message = "Method '{$method}' does not exist on the Cache facade";

            throw new \BadMethodCallException($message);
        }
    }

    /**
     * Check if method exists on Cache facade (either regular method or macro).
     */
    private function cacheMethodExists(string $method): bool
    {
        if (Cache::hasMacro($method)) {
            return true;
        }

        $cacher = Cache::getFacadeRoot();

        return method_exists($cacher, $method);
    }
}
