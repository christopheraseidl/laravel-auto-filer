<?php

namespace christopheraseidl\HasUploads\Handlers;

use christopheraseidl\HasUploads\Jobs\DeleteUploadDirectory;
use christopheraseidl\HasUploads\Payloads\DeleteUploadDirectoryPayload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HandleUploadsOnModelDeletion
{
    public function handle(Model $model): void
    {
        $payload = DeleteUploadDirectoryPayload::make(
            Str::snake(class_basename($this)),
            $model->id,
            $model->getUploadPath()
        );

        DeleteUploadDirectory::dispatch($payload);
    }
}
