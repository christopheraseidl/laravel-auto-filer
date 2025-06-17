<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploads as DeleteUploadsContract;
use christopheraseidl\HasUploads\Jobs\Contracts\FileDeleter;
use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploads as DeleteUploadsPayload;
use christopheraseidl\HasUploads\Support\FileOperationType;

/**
 * Job to delete multiple upload files using a retry mechanism with circuit breaker protection.
 *
 * This job processes a list of file paths and attempts to delete each one using
 * the FileDeleter service, which provides retry logic and failure handling.
 * Unlike DeleteUploadDirectory which removes entire directories, this job targets
 * specific individual files.
 *
 * The job includes circuit breaker protection and retry mechanisms to handle
 * temporary file system failures gracefully.
 */
final class DeleteUploads extends Job implements DeleteUploadsContract
{
    protected FileDeleter $deleter;

    public function __construct(
        private readonly DeleteUploadsPayload $payload
    ) {
        $this->config();
    }

    /**
     * Execute the file deletion operation for all files in the payload.
     *
     * Iterates through each file path and uses the FileDeleter service to
     * attempt deletion with retry logic and circuit breaker protection.
     * Each file is processed individually, so partial failures are possible.
     */
    public function handle(): void
    {
        $this->handleJob(function () {
            foreach ($this->getPayload()->getFilePaths() as $file) {
                $this->deleter->attemptDelete($this->getPayload()->getDisk(), $file);
            }
        });
    }

    /**
     * Get the operation type identifier for this job.
     *
     * Uses the operation type and scope from the payload, allowing for
     * flexible operation categorization based on the specific deletion context.
     *
     * @return string The operation type for circuit breaker grouping and tracking
     */
    public function getOperationType(): string
    {
        return FileOperationType::get($this->getPayload()->getOperationType(), $this->getPayload()->getOperationScope());
    }

    /**
     * Get a unique identifier for this specific job instance.
     *
     * Uses the payload's key to ensure job uniqueness, which is typically
     * based on the model and context that initiated the deletion.
     *
     * @return string The unique job identifier from the payload
     */
    public function uniqueId(): string
    {
        return $this->getPayload()->getKey();
    }

    public function getPayload(): DeleteUploadsPayload
    {
        return $this->payload;
    }

    protected function config()
    {
        parent::config();

        $this->deleter = app()->make(FileDeleter::class);
    }
}
