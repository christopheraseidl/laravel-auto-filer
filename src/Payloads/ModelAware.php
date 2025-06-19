<?php

namespace christopheraseidl\ModelFiler\Payloads;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Payloads\Contracts\ModelAware as ModelAwareContract;
use christopheraseidl\ModelFiler\Traits\HasDisk;
use Illuminate\Database\Eloquent\Model;

/**
 * Provides model context for upload operation payloads.
 */
abstract class ModelAware extends Payload implements ModelAwareContract
{
    use HasDisk;

    public function __construct(
        private readonly string $modelClass,
        private readonly int $modelId,
        private readonly ?string $modelAttribute,
        private readonly ?string $modelAttributeType,
        private readonly OperationType $operationType,
        private readonly OperationScope $operationScope,
        private readonly string $disk,
        private readonly ?array $filePaths = null,
        private readonly ?string $newDir = null
    ) {}

    /**
     * Generate unique identifier combining model context and file hash.
     */
    public function getKey(): string
    {
        $modelIdentifier = sprintf(
            '%s_%s_%s_%s',
            $this->operationType->value,
            $this->operationScope->value,
            $this->modelClass,
            $this->modelId
        );

        $fileIdentifier = md5(serialize($this->filePaths));

        return "{$modelIdentifier}_{$fileIdentifier}";
    }

    public function toArray(): array
    {
        return [
            'modelClass' => $this->modelClass,
            'modelId' => $this->modelId,
            'modelAttribute' => $this->modelAttribute,
            'modelAttributeType' => $this->modelAttributeType,
            'operationType' => $this->operationType,
            'operationScope' => $this->operationScope,
            'disk' => $this->disk,
            'filePaths' => $this->filePaths,
            'newDir' => $this->newDir,
        ];
    }

    /**
     * Retrieve the model instance from the database.
     */
    public function resolveModel(): Model
    {
        return $this->getModelClass()::findOrFail($this->getModelId());
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function getModelId(): int
    {
        return $this->modelId;
    }

    public function getModelAttribute(): ?string
    {
        return $this->modelAttribute;
    }

    public function getModelAttributeType(): ?string
    {
        return $this->modelAttributeType;
    }

    public function getOperationType(): OperationType
    {
        return $this->operationType;
    }

    public function getOperationScope(): OperationScope
    {
        return $this->operationScope;
    }

    public function getFilePaths(): ?array
    {
        return $this->filePaths;
    }

    public function getNewDir(): ?string
    {
        return $this->newDir;
    }
}
