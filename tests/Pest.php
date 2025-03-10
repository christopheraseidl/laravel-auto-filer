<?php

use christopheraseidl\HasUploads\Facades\UploadService;
use christopheraseidl\HasUploads\Jobs\CleanOrphanedUploads;
use christopheraseidl\HasUploads\Jobs\DeleteUploadDirectory;
use christopheraseidl\HasUploads\Payloads\CleanOrphanedUploads as CleanOrphanedUploadsPayload;
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

    $this->disk = UploadService::getDisk();

    Storage::fake($this->disk);
})
    ->afterEach(function () {
        $this->artisan('migrate:reset');
    })
    ->in('*');

// CleanOrphanedUploads
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

// DeleteUploadDirectory
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

// Jobs
uses()->beforeEach(function () {
    $this->payload = new TestJobPayload;

    $this->job = new TestJob;
    $this->job->payload = $this->payload;
})->in('Jobs/Job');
