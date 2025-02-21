<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Payloads\Contracts\ModelAware;
use christopheraseidl\HasUploads\Support\FileOperationType;
use christopheraseidl\HasUploads\Traits\AttemptsFileMoves;

final class MoveUploads extends Job
{
    use AttemptsFileMoves;

    public function __construct(
        private readonly ModelAware $payload
    ) {}

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

    public function getPayload(): ModelAware
    {
        return $this->payload;
    }
}
