<?php

namespace christopheraseidl\HasUploads\Payloads\Contracts;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use Illuminate\Database\Eloquent\Model;

interface ModelAware extends Payload
{
    public function __construct(
        string $modelClass,
        int $modelId,
        ?string $modelAttribute = null,
        ?string $modelAttributeType = null,
        OperationType $operationType,
        OperationScope $operationScope,
        string $disk,
        ?array $filePaths = null,
        ?string $newDir = null
    );

    public function resolveModel(): Model;

    public function getModelClass(): string;

    public function getModelId(): int;

    public function getModelAttribute(): ?string;

    public function getModelAttributeType(): ?string;

    public function getOperationType(): OperationType;

    public function getOperationScope(): OperationScope;

    public function getDisk(): string;

    public function getFilePaths(): ?array;

    public function getNewDir(): ?string;
}
