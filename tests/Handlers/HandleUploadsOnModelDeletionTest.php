<?php

namespace christopheraseidl\HasUploads\Tests\Handlers;

use christopheraseidl\HasUploads\Jobs\DeleteUploadDirectory;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Bus::fake();

    $this->modelClass = 'test_model';

    $this->id = $this->model->id;

    $this->disk = config()->set('has-uploads.disk', 'public');

    $this->path = $this->model->getUploadPath();

    $this->model->delete();
});

it('dispatches the correctly configured delete upload directory job on model deletion', function () {
    Bus::assertDispatched(DeleteUploadDirectory::class, function ($job) {
        $job = Reflect::on($job);

        return $job->getPayload()->getModelClass() === $this->modelClass
            && $job->getPayload()->getId() === $this->id
            && $job->getPayload()->getDisk() === 'public'
            && $job->getPayload()->getPath() === $this->path;
    });
});
