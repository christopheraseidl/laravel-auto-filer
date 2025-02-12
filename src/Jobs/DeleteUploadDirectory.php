<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Contracts\DeleteUploadDirectoryPayload;
use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Facades\UploadService;
use christopheraseidl\HasUploads\Support\FileOperationType;

final class DeleteUploadDirectory extends BaseUploadJob
{
    public function __construct(
        private readonly DeleteUploadDirectoryPayload $payload
    ) {}

    public function handle(): void
    {
        $this->handleJob(function () {
            UploadService::deleteDirectory($this->payload->getPath());
        });
    }

    public function getOperationType(): string
    {
        return FileOperationType::get(OperationType::Delete, OperationScope::Directory);
    }

    public function uniqueId(): string
    {
        return "delete_directory_{$this->payload->getId()}";
    }

    public function getPayload(): DeleteUploadDirectoryPayload
    {
        return $this->payload;
    }
}
