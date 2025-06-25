<?php

namespace christopheraseidl\ModelFiler\Payloads;

use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryContract;
use christopheraseidl\ModelFiler\Traits\HasPath;

/**
 * Provides model and path data to the DeleteUploadDirectory job.
 */
class DeleteUploadDirectory extends Payload implements DeleteUploadDirectoryContract
{
    use HasPath;

    public function __construct(
        private readonly string $modelClass,
        private readonly int $id,
        private readonly string $disk,
        private readonly string $path
    ) {}

    /**
     * Return model class name.
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Return model identifier.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Return unique identifier for this payload instance.
     */
    public function getKey(): string
    {
        return "delete_upload_directory_{$this->getModelClass()}_{$this->getId()}";
    }

    /**
     * Return storage disk name.
     */
    public function getDisk(): string
    {
        return $this->disk;
    }

    /**
     * Determine whether individual events should be broadcast.
     */
    public function shouldBroadcastIndividualEvents(): bool
    {
        return true;
    }

    /**
     * Convert payload data to array representation.
     */
    public function toArray(): array
    {
        return [
            'modelClass' => $this->modelClass,
            'id' => $this->id,
            'path' => $this->path,
        ];
    }
}
