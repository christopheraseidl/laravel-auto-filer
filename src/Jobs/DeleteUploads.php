<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploads as DeleteUploadsContract;
use christopheraseidl\HasUploads\Jobs\Contracts\FileDeleter;
use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploads as DeleteUploadsPayload;
use christopheraseidl\HasUploads\Support\FileOperationType;

/**
 * Deletes multiple upload files using retry mechanism with circuit breaker protection.
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
     * Execute file deletion operation for all files in payload.
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
     * Get operation type identifier from payload for job grouping.
     */
    public function getOperationType(): string
    {
        return FileOperationType::get(
            $this->getPayload()->getOperationType(),
            $this->getPayload()->getOperationScope()
        );
    }

    /**
     * Get unique identifier from payload key.
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

        $this->deleter = app()->make(FileDeleter::class); // Initialize file deleter service
    }
}
