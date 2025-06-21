<?php

namespace christopheraseidl\ModelFiler\Payloads;

use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryContract;
use christopheraseidl\ModelFiler\Traits\HasDisk;
use christopheraseidl\ModelFiler\Traits\HasPath;

/**
 * Provides model and path data to the DeleteUploadDirectory job.
 */
class DeleteUploadDirectory extends Payload implements DeleteUploadDirectoryContract
{
    use HasDisk, HasPath;

    public function __construct(
        private readonly string $modelClass,
        private readonly int $id,
        private readonly string $disk,
        private readonly string $path
    ) {}

    public function getKey(): string
    {
        return "delete_upload_directory_{$this->modelClass}_{$this->id}";
    }

    public function shouldBroadcastIndividualEvents(): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return [
            'modelClass' => $this->modelClass,
            'id' => $this->id,
            'path' => $this->path,
        ];
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
