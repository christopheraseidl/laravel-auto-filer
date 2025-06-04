<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploads as DeleteUploadsContract;
use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploads as DeleteUploadsPayload;
use christopheraseidl\HasUploads\Support\FileOperationType;
use christopheraseidl\HasUploads\Traits\AttemptsFileDeletion;

final class DeleteUploads extends Job implements DeleteUploadsContract
{
    use AttemptsFileDeletion;

    public function __construct(
        private readonly DeleteUploadsPayload $payload
    ) {
        $this->config();
    }

    public function handle(): void
    {
        $this->handleJob(function () {
            foreach ($this->getPayload()->getFilePaths() as $file) {
                $this->attemptDelete($this->getPayload()->getDisk(), $file);
            }
        });
    }

    public function getOperationType(): string
    {
        return FileOperationType::get($this->getPayload()->getOperationType(), $this->getPayload()->getOperationScope());
    }

    public function uniqueId(): string
    {
        return $this->getPayload()->getKey();
    }

    public function getPayload(): DeleteUploadsPayload
    {
        return $this->payload;
    }
}
