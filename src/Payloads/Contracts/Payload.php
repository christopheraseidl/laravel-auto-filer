<?php

namespace christopheraseidl\ModelFiler\Payloads\Contracts;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Creates payload instances with dynamic parameter resolution.
 */
interface Payload extends Arrayable
{
    /**
     * Create a new payload instance with dynamic parameter resolution.
     */
    public static function make(...$args): ?static;

    /**
     * Determine if individual events should be broadcast for each operation.
     */
    public function shouldBroadcastIndividualEvents(): bool;

    /**
     * Get the unique identifier for this payload.
     */
    public function getKey(): string;

    /**
     * Get the disk where files are stored.
     */
    public function getDisk(): string;
}
