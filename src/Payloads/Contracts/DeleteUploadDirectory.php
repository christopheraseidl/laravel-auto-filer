<?php

namespace christopheraseidl\HasUploads\Payloads\Contracts;

use christopheraseidl\HasUploads\Contracts\SinglePath;

interface DeleteUploadDirectory extends Payload, SinglePath
{
    public function __construct(
        string $modelClass,
        int $id,
        string $disk,
        string $path
    );

    public function getModelClass(): string;

    public function getId(): int;

    public function getDisk(): string;

    public function getPath(): string;
}
