<?php

namespace christopheraseidl\HasUploads\Contracts;

interface JobFactory
{
    public function create(string $jobClass, string $payloadClass, array $args): ?object;
}
