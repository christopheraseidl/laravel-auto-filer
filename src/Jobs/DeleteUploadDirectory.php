<?php

namespace christopheraseidl\ModelFiler\Jobs;

use christopheraseidl\ModelFiler\Enums\OperationScope;
use christopheraseidl\ModelFiler\Enums\OperationType;
use christopheraseidl\ModelFiler\Jobs\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryContract;
use christopheraseidl\ModelFiler\Jobs\Contracts\FileDeleter;
use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryPayload;
use christopheraseidl\ModelFiler\Support\FileOperationType;
use christopheraseidl\ModelFiler\Traits\HasFileDeleter;
use Illuminate\Support\Str;

/**
 * Deletes entire upload directory and all its contents.
 */
class DeleteUploadDirectory extends Job implements DeleteUploadDirectoryContract
{
    use HasFileDeleter;

    public function __construct(
        private readonly DeleteUploadDirectoryPayload $payload
    ) {
        $this->config();
    }

    /**
     * Execute directory deletion operation as a closure in Job's handleJob().
     */
    public function handle(): void
    {
        $this->handleJob(function () {
            $this->executeDeletion();
        });
    }

    /**
     * Execute the directory deletion.
     */
    public function executeDeletion(): void
    {
        $this->getDeleter()->attemptDelete($this->getPayload()->getDisk(), $this->getPayload()->getPath());
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
        return "{$this->getOperationType()}_".Str::snake(class_basename($this->getPayload()->getModelClass()))."_{$this->getPayload()->getId()}";
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
