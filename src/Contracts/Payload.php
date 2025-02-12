<?php

namespace christopheraseidl\HasUploads\Contracts;

use Illuminate\Contracts\Support\Arrayable;

interface Payload extends Arrayable, ConstructiblePayload
{
    public static function make(...$args): ?static;

    public function shouldBroadcastIndividualEvents(): bool;

    public function getKey(): string;

    public function getDisk(): string;
}
