<?php

namespace christopheraseidl\ModelFiler\Payloads\Contracts;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use Illuminate\Database\Eloquent\Model;

/**
 * Provides model context for upload operation payloads.
 */
interface ModelAware extends Payload
{
    public function __construct(
        string $modelClass,
        int $modelId,
        ?string $modelAttribute,
        ?string $modelAttributeType,
        OperationType $operationType,
        OperationScope $operationScope,
        string $disk,
        ?array $filePaths = null,
        ?string $newDir = null
    );

    /**
     * Retrieve the model instance from the database.
     */
    public function resolveModel(): Model;

    /**
     * Return model class name.
     */
    public function getModelClass(): string;

    /**
     * Return model identifier.
     */
    public function getModelId(): int;

    /**
     * Return model attribute name.
     */
    public function getModelAttribute(): ?string;

    /**
     * Return model attribute type.
     */
    public function getModelAttributeType(): ?string;

    /**
     * Return operation type enumeration.
     */
    public function getOperationType(): OperationType;

    /**
     * Return operation scope enumeration.
     */
    public function getOperationScope(): OperationScope;

    /**
     * Return file paths array.
     */
    public function getFilePaths(): ?array;

    /**
     * Return new directory path.
     */
    public function getNewDir(): ?string;

    /**
     * Generate unique identifier combining model context and file hash.
     */
    public function getKey(): string;

    /**
     * Convert payload data to array representation.
     */
    public function toArray(): array;
}
