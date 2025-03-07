<?php

namespace christopheraseidl\HasUploads\Handlers;

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Jobs\DeleteUploadDirectory;
use christopheraseidl\HasUploads\Payloads\DeleteUploadDirectory as DeleteUploadDirectoryPayload;
use Illuminate\Database\Eloquent\Model;

class ModelDeletionUploadHandler
{
    protected string $disk;

    public function __construct(
        protected UploadService $uploadService
    ) {
        $this->disk = $uploadService->getDisk();
    }

    public function handle(Model $model): void
    {
        $payload = DeleteUploadDirectoryPayload::make(
            get_class($model),
            $model->id,
            $this->disk,
            $model->getUploadPath()
        );

        DeleteUploadDirectory::dispatch($payload);
    }
}
