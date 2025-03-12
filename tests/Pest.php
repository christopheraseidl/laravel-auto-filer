<?php

use christopheraseidl\HasUploads\Jobs\CleanOrphanedUploads;
use christopheraseidl\HasUploads\Jobs\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryJobContract;
use christopheraseidl\HasUploads\Jobs\DeleteUploadDirectory;
use christopheraseidl\HasUploads\Payloads\CleanOrphanedUploads as CleanOrphanedUploadsPayload;
use christopheraseidl\HasUploads\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryPayloadContract;
use christopheraseidl\HasUploads\Payloads\DeleteUploadDirectory as DeleteUploadDirectoryPayload;
use christopheraseidl\HasUploads\Tests\TestCase;
use christopheraseidl\HasUploads\Tests\TestClasses\TestJob;
use christopheraseidl\HasUploads\Tests\TestClasses\TestJobPayload;
use christopheraseidl\HasUploads\Tests\TestModels\TestModel;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Support\Facades\Storage;

uses(TestCase::class)->in(__DIR__);

// General setup
uses()->beforeEach(function () {
    $this->artisan('make:queue-table');

    $this->artisan('make:queue-batches-table');

    $this->artisan('make:queue-failed-table');

    $this->loadMigrationsFrom(__DIR__.'/TestMigrations/create_test_models_table.php');

    $this->artisan('migrate');

    $this->model = new TestModel;

    $this->model->save();

    $this->disk = config()->get('has-uploads.disk', 'public');

    Storage::fake($this->disk);
})
    ->afterEach(function () {
        $this->artisan('migrate:reset');
    })
    ->in('*');

// Handlers/ModelDeletionHandler
uses()->beforeEach(function () {
    $this->payload = app()->makeWith(DeleteUploadDirectoryPayloadContract::class, [
        'modelClass' => get_class($this->model),
        'id' => $this->model->id,
        'disk' => $this->disk,
        'path' => $this->model->getUploadPath(),
    ]);

    $this->job = app()->makeWith(DeleteUploadDirectoryJobContract::class, [
        'payload' => $this->payload,
    ]);
})->in('Handlers/ModelDeletionHandler');

// Jobs/CleanOrphanedUploads
uses()->beforeEach(function () {
    $this->path = '/uploads';

    $this->payload = new CleanOrphanedUploadsPayload(
        $this->disk,
        $this->path,
        24
    );

    $this->cleaner = Reflect::on(
        new CleanOrphanedUploads($this->payload)
    );
})->in('Jobs/CleanOrphanedUploads');

// Jobs/DeleteUploadDirectory
uses()->beforeEach(function () {
    $this->path = 'test_models/1';
    $this->payload = new DeleteUploadDirectoryPayload(
        TestModel::class,
        $this->model->id,
        $this->disk,
        $this->path
    );

    $this->job = Reflect::on(
        new DeleteUploadDirectory($this->payload)
    );
})->in('Jobs/DeleteUploadDirectory');

// Jobs/Job
uses()->beforeEach(function () {
    $this->payload = new TestJobPayload;

    $this->job = new TestJob;
    $this->job->payload = $this->payload;
})->in('Jobs/Job');
