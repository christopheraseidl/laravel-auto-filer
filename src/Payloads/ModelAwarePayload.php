<?php

namespace christopheraseidl\HasUploads\Payloads;

use christopheraseidl\HasUploads\Contracts\ModelAwarePayload as ModelAwarePayloadContract;
use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Traits\HasDisk;
use Illuminate\Database\Eloquent\Model;

abstract class ModelAwarePayload extends Payload implements ModelAwarePayloadContract
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

    public static function make(...$args): ?static
    {
        [
            $modelClass,
            $modelId,
            $modelAttribute,
            $modelAttributeType,
            $operationType,
            $operationScope,
            $disk,
            $filePaths
        ] = $args;

        return new static(
            modelClass: $modelClass,
            modelId: $modelId,
            modelAttribute: $modelAttribute,
            modelAttributeType: $modelAttributeType,
            operationType: $operationType,
            operationScope: $operationScope,
            disk: $disk,
            filePaths: $filePaths
        );
    }

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
