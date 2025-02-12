<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Contracts\ModelAwarePayload;
use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Support\FileOperationType;
use christopheraseidl\HasUploads\Traits\AttemptsFileMoves;

final class MoveUploads extends BaseUploadJob
{
    use AttemptsFileMoves;

    public function __construct(
        private readonly ModelAwarePayload $payload
    ) {}

    public static function make(ModelAwarePayload $payload): ?static
    {
        return new static($payload);
    }

    public function handle(): void
    {
        $model = $this->getPayload()->resolveModel();

        $this->handleJob(function () use ($model) {
            $model->{$this->getPayload()->getModelAttribute()} = array_map(
                function ($oldPath) {
                    return $this->attemptMove(
                        $this->getPayload()->getDisk(),
                        $oldPath,
                        $this->getPayload()->getNewDir());
                },
                $this->getPayload()->getFilePaths()
            );

            $model->saveQuietly();
        });
    }

    public function getOperationType(): string
    {
        return FileOperationType::get(OperationType::Move, OperationScope::File);
    }

    public function uniqueId(): string
    {
        return $this->getPayload()->getKey();
    }

    public function getPayload(): ModelAwarePayload
    {
        return $this->payload;
    }
}
