<?php

namespace christopheraseidl\ModelFiler\Contracts;

/**
 * Provides caching capabilities for implementing classes.
 */
interface Cacheable
{
    /**
     * Get cached value.
     */
    public function cacheGet(string $key, mixed $default = null): mixed;

    /**
     * Store value in cache.
     */
    public function cachePut(string $key, mixed $value, int|\DateTimeInterface|\DateInterval|null $ttl = null): bool;

    /**
     * Increment cached numeric value.
     */
    public function cacheIncrement(string $key, int $value = 1): int|bool;

    /**
     * Remove value from cache.
     */
    public function cacheForget(string $key): bool;
}
