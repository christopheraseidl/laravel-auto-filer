<?php

namespace christopheraseidl\HasUploads\Handlers;

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploadDirectory;
use christopheraseidl\HasUploads\Payloads\DeleteUploadDirectory as DeleteUploadDirectoryPayload;
use Illuminate\Database\Eloquent\Model;

class ModelDeletionHandler
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
            $model::class,
            $model->id,
            $this->disk,
            $model->getUploadPath()
        );

        $delete = app()->makeWith(DeleteUploadDirectory::class, [
            'payload' => $payload,
        ]);

        dispatch($delete);
    }
}
