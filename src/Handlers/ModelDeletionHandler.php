<?php

namespace christopheraseidl\HasUploads\Handlers;

use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploadDirectory;
use christopheraseidl\HasUploads\Payloads\DeleteUploadDirectory as DeleteUploadDirectoryPayload;
use christopheraseidl\HasUploads\Services\Contracts\UploadService;
use Illuminate\Database\Eloquent\Model;

/**
 * Handles file cleanup when models are deleted.
 */
class ModelDeletionHandler
{
    protected string $disk;

    public function __construct(
        protected UploadService $uploadService
    ) {
        $this->disk = $uploadService->getDisk();
    }

    /**
     * Delete the entire upload directory for the model.
     */
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
