<?php

namespace christopheraseidl\HasUploads\Tests\Handlers;

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Handlers\ModelDeletionHandler;
use christopheraseidl\HasUploads\Jobs\DeleteUploadDirectory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Bus::fake();

    $this->modelClass = get_class($this->model);

    $this->id = $this->model->id;

    $this->disk = config()->set('has-uploads.disk', 'public');

    $this->path = $this->model->getUploadPath();

    $this->handler = new ModelDeletionHandler(app(UploadService::class));
});

it('dispatches the correctly configured delete upload directory job when handle() is called', function () {
    $this->handler->handle($this->model);

    Bus::assertDispatched(DeleteUploadDirectory::class, function ($job) {
        $payload = $job->getPayload();

        return $payload->getModelClass() === $this->modelClass
            && $payload->getId() === $this->id
            && $payload->getDisk() === 'public'
            && $payload->getPath() === $this->path;
    });
});
