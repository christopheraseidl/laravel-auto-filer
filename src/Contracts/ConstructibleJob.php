<?php

namespace christopheraseidl\HasUploads\Contracts;

interface ConstructibleJob
{
    /**
     * Construct a job instance with a payload.
     * Returns null if the job cannot be constructed with the given payload.
     */
    public static function make(Payload $payload): ?static;
}
