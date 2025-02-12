<?php

namespace christopheraseidl\HasUploads\Contracts;

interface DeleteUploadDirectoryPayload extends Payload, SinglePath
{
    public function __construct(
        string $modelClass,
        int $id,
        string $disk,
        string $path
    );

    public function getId(): int;
}
