<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\ModelDeletionHandler;

use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploadDirectory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Bus::fake();

    $this->model->delete();
});

it('dispatches the correctly configured delete upload directory job on model deletion', function () {
    Bus::assertDispatched($this->job::class, function ($job) {
        $payload = $job->getPayload();

        return $job instanceof DeleteUploadDirectory
            && $payload == $this->payload;
    });
});
