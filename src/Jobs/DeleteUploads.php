<?php

namespace christopheraseidl\HasUploads\Jobs;

use christopheraseidl\HasUploads\Payloads\Contracts\ModelAware;
use christopheraseidl\HasUploads\Support\FileOperationType;
use Illuminate\Support\Facades\Storage;

final class DeleteUploads extends Job
{
    public function __construct(
        private readonly ModelAware $payload
    ) {}

    public function handle(): void
    {
        $model = $this->getPayload()->resolveModel();

        $this->handleJob(function () use ($model) {
            foreach ($this->getPayload()->getFilePaths() as $file) {
                Storage::disk($this->getPayload()->getDisk())->delete($file);
            }

            $model->saveQuietly();
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

    public function getPayload(): ModelAware
    {
        return $this->payload;
    }
}
