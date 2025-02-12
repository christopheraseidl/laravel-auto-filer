<?php

use christopheraseidl\HasUploads\Facades\UploadService;
use christopheraseidl\HasUploads\Tests\TestCase;
use christopheraseidl\HasUploads\Tests\TestModels\TestModel;
use Illuminate\Support\Facades\Storage;

uses(TestCase::class)->in(__DIR__);

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
