<?php

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager;
use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Handlers\ModelUpdateHandler;
use christopheraseidl\HasUploads\Jobs\Contracts\Builder;
use christopheraseidl\Reflect\Reflect;

beforeEach(function () {
    $this->handler = Reflect::on(new ModelUpdateHandler(
        app(UploadService::class),
        app(Builder::class),
        app(BatchManager::class),
        app(ModelFileChangeTracker::class)
    ));
});

it('returns an empty array when creating jobs for unchanged attributes', function () {
    $stringJobs = $this->handler->createJobsFromAttribute($this->model, 'string', 'images');
    $arrayJobs = $this->handler->createJobsFromAttribute($this->model, 'array', 'documents');

    expect($stringJobs)->toBeEmpty()
        ->and($arrayJobs)->toBeEmpty();
});
