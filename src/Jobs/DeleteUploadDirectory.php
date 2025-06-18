<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryContract;
use christopheraseidl\HasUploads\Jobs\Contracts\FileDeleter;
use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryPayload;
use christopheraseidl\HasUploads\Support\FileOperationType;
use Illuminate\Support\Str;

/**
 * Deletes entire upload directory and all its contents.
 */
final class DeleteUploadDirectory extends Job implements DeleteUploadDirectoryContract
{
    protected FileDeleter $deleter;

    public function __construct(
        private readonly DeleteUploadDirectoryPayload $payload
    ) {
        $this->config();
    }

    /**
     * Execute directory deletion operation.
     */
    public function handle(): void
    {
        $this->handleJob(function () {
            $this->deleter->attemptDelete($this->getPayload()->getDisk(), $this->payload->getPath());
        });
    }

    /**
     * Get operation type identifier for job grouping.
     */
    public function getOperationType(): string
    {
        return FileOperationType::get(OperationType::Delete, OperationScope::Directory);
    }

    /**
     * Get unique identifier combining operation type with model details.
     */
    public function uniqueId(): string
    {
        return "{$this->getOperationType()}_".Str::snake(class_basename($this->payload->getModelClass()))."_{$this->payload->getId()}";
    }

    public function getPayload(): DeleteUploadDirectoryPayload
    {
        return $this->payload;
    }

    protected function config()
    {
        parent::config();

        $this->deleter = app()->make(FileDeleter::class); // Initialize file deleter service
    }
}
