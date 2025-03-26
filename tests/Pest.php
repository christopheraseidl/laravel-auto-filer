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
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

pest()->extends(TestCase::class)->in(__DIR__);

// General setup
pest()->beforeEach(function () {
    $this->model = new TestModel;

    $this->model->save();

    $this->disk = config()->get('has-uploads.disk', 'public');

    Storage::fake($this->disk);
})
    ->in('*');

// Handlers/ModelCreationHandler
pest()->beforeEach(function () {
    Bus::fake();

    $this->stringFillableName = 'my-image.png';
    $this->arrayFillableName = 'important-document.pdf';
});

// Handlers/ModelDeletionHandler
pest()->beforeEach(function () {
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

// Handlers/ModelUpdateHandler
pest()->beforeEach(function () {
    Bus::fake();

    $string = 'image.jpg';
    Storage::disk($this->disk)->put($string, 100);

    $array = ['document1.doc', 'document2.md'];
    Storage::disk($this->disk)->put($array[0], 200);
    Storage::disk($this->disk)->put($array[1], 200);

    $this->model->string = $string;
    $this->model->array = $array;
    $this->model->saveQuietly();

    $this->newString = 'new-image.png';
    Storage::disk($this->disk)->put($this->newString, 100);
    $newArrayItem = 'new-doc.txt';
    $this->newArray = [$this->model->array[1], $newArrayItem];
    Storage::disk($this->disk)->put($newArrayItem, 200);
})->in('Handlers/ModelUpdateHandler');

// Jobs/CleanOrphanedUploads
pest()->beforeEach(function () {
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
pest()->beforeEach(function () {
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
pest()->beforeEach(function () {
    $this->payload = new TestJobPayload;

    $this->job = new TestJob;
    $this->job->payload = $this->payload;
})->in('Jobs/Job');
