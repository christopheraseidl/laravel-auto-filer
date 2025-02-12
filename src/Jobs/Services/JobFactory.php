<?php

namespace christopheraseidl\HasUploads\Jobs\Services;

use christopheraseidl\HasUploads\Contracts\JobFactory as JobFactoryContract;

class JobFactory implements JobFactoryContract
{
    public function create(
        string $jobClass,
        string $payloadClass,
        array $args = []
    ): ?object {
        $payload = $payloadClass::make(...$args);

        return $jobClass::make($payload);
    }
}
