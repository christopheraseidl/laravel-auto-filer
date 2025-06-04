<?php

namespace christopheraseidl\HasUploads\Jobs\Contracts;

use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploads as DeleteUploadsContract;

interface DeleteUploads extends Job
{
    public function __construct(DeleteUploadsContract $payload);

    public function attemptDelete(string $disk, string $path, int $maxAttempts = 3): bool;
}
