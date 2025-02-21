<?php

namespace christopheraseidl\HasUploads\Payloads\Contracts;

use Illuminate\Contracts\Support\Arrayable;

interface Payload extends Arrayable
{
    public static function make(...$args): ?static;

    public function shouldBroadcastIndividualEvents(): bool;

    public function getKey(): string;

    public function getDisk(): string;
}
