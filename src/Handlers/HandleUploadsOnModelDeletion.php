<?php

namespace christopheraseidl\HasUploads\Handlers;

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Jobs\DeleteUploadDirectory;
use christopheraseidl\HasUploads\Payloads\DeleteUploadDirectoryPayload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HandleUploadsOnModelDeletion
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
            Str::snake(class_basename($model)),
            $model->id,
            $this->disk,
            $model->getUploadPath()
        );

        DeleteUploadDirectory::dispatch($payload);
    }
}
