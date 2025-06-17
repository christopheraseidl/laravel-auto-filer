<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Enums\OperationScope;
use christopheraseidl\HasUploads\Enums\OperationType;
use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryContract;
use christopheraseidl\HasUploads\Jobs\Contracts\FileDeleter;
use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryPayload;
use christopheraseidl\HasUploads\Support\FileOperationType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Job to delete an entire upload directory and all its contents.
 *
 * This job is typically used when a model is deleted and its associated
 * upload directory needs to be cleaned up. It deletes the entire directory
 * structure including all files and subdirectories within it.
 *
 * The job includes circuit breaker protection to prevent cascading failures
 * during file system operations.
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
     * Execute the directory deletion operation.
     *
     * Deletes the entire directory specified in the payload, including all
     * files and subdirectories within it. The operation is protected by
     * the circuit breaker pattern.
     */
    public function handle(): void
    {
        $this->handleJob(function () {
            Storage::disk($this->payload->getDisk())->deleteDirectory($this->payload->getPath());
        });
    }

    /**
     * Get the operation type identifier for this job.
     *
     * Used for circuit breaker grouping and operation tracking.
     *
     * @return string The operation type combining delete operation and directory scope
     */
    public function getOperationType(): string
    {
        return FileOperationType::get(OperationType::Delete, OperationScope::Directory);
    }

    /**
     * Get a unique identifier for this specific job instance.
     *
     * Combines the operation type with the model class and ID to ensure
     * each directory deletion job is uniquely identifiable and prevents
     * duplicate jobs for the same directory.
     *
     * @return string Unique identifier in format: "operation_type_model_class_model_id"
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

        $this->deleter = app()->make(FileDeleter::class);
    }
}
