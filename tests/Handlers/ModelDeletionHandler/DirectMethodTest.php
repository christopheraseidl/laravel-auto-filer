<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\ModelDeletionHandler;

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Handlers\ModelDeletionHandler;
use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploadDirectory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Bus::fake();

    $this->handler = new ModelDeletionHandler(app(UploadService::class));
});

it('dispatches the correctly configured delete upload directory job when handle() is called', function () {
    $this->handler->handle($this->model);

    Bus::assertDispatched($this->job::class, function ($job) {
        $payload = $job->getPayload();

        return $job instanceof DeleteUploadDirectory
            && $payload == $this->payload;
    });
});
