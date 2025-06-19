<?php

namespace christopheraseidl\ModelFiler\Handlers;

use christopheraseidl\ModelFiler\Jobs\Contracts\DeleteUploadDirectory;
use christopheraseidl\ModelFiler\Payloads\DeleteUploadDirectory as DeleteUploadDirectoryPayload;
use christopheraseidl\ModelFiler\Services\Contracts\FileService;
use Illuminate\Database\Eloquent\Model;

/**
 * Handles file cleanup when models are deleted.
 */
class ModelDeletionHandler
{
    protected string $disk;

    public function __construct(
        protected FileService $fileService
    ) {
        $this->disk = $fileService->getDisk();
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
