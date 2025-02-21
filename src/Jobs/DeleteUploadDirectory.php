<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryPayload;
use christopheraseidl\HasUploads\Support\FileOperationType;
use Illuminate\Support\Facades\Storage;

final class DeleteUploadDirectory extends Job
{
    public function __construct(
        private readonly DeleteUploadDirectoryPayload $payload
    ) {}

    public function handle(): void
    {
        $this->handleJob(function () {
            Storage::deleteDirectory($this->payload->getPath());
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
