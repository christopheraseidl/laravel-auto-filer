<?php

namespace christopheraseidl\ModelFiler\Payloads\Contracts;

use christopheraseidl\ModelFiler\Contracts\SinglePath;

/**
 * Provides model and path data to the DeleteUploadDirectory job.
 */
interface DeleteUploadDirectory extends Payload, SinglePath
{
    public function __construct(
        string $modelClass,
        int $id,
        string $disk,
        string $path
    );

    /**
     * Return model class name.
     */
    public function getModelClass(): string;

    /**
     * Return model identifier.
     */
    public function getId(): int;

    /**
     * Return file path.
     */
    public function getPath(): string;

    /**
     * Return unique identifier for this payload instance.
     */
    public function getKey(): string;

    /**
     * Determine whether individual events should be broadcast.
     */
    public function shouldBroadcastIndividualEvents(): bool;

    /**
     * Convert payload data to array representation.
     */
    public function toArray(): array;
}
