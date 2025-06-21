<?php

namespace christopheraseidl\ModelFiler\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Implements caching methods using Laravel's Cache facade.
 */
trait InteractsWithCache
{
    /**
     * Get cached value.
     */
    public function cacheGet(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Store value in cache.
     */
    public function cachePut(string $key, mixed $value, int|\DateTimeInterface|\DateInterval|null $ttl = null): bool
    {
        return Cache::put($key, $value, $ttl);
    }

    /**
     * Increment cached numeric value.
     */
    public function cacheIncrement(string $key, int $value = 1): int|bool
    {
        return Cache::increment($key, $value);
    }

    /**
     * Remove value from cache.
     */
    public function cacheForget(string $key): bool
    {
        return Cache::forget($key);
    }
}
