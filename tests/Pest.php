<?php

use christopheraseidl\ModelFiler\Handlers\Services\BatchManager;
use christopheraseidl\ModelFiler\Jobs\CleanOrphanedUploads;
use christopheraseidl\ModelFiler\Jobs\Contracts\BuilderValidator as BuilderValidatorContract;
use christopheraseidl\ModelFiler\Jobs\Contracts\CircuitBreaker as CircuitBreakerContract;
use christopheraseidl\ModelFiler\Jobs\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryJobContract;
use christopheraseidl\ModelFiler\Jobs\Contracts\FileDeleter as FileDeleterContract;
use christopheraseidl\ModelFiler\Jobs\DeleteUploadDirectory;
use christopheraseidl\ModelFiler\Jobs\DeleteUploads;
use christopheraseidl\ModelFiler\Jobs\MoveUploads;
use christopheraseidl\ModelFiler\Jobs\Services\Builder;
use christopheraseidl\ModelFiler\Jobs\Services\CircuitBreaker;
use christopheraseidl\ModelFiler\Jobs\Services\FileDeleter;
use christopheraseidl\ModelFiler\Jobs\Services\FileMover;
use christopheraseidl\ModelFiler\Jobs\Validators\BuilderValidator;
use christopheraseidl\ModelFiler\Payloads\Contracts\CleanOrphanedUploads as CleanOrphanedUploadsPayload;
use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploadDirectory as DeleteUploadDirectoryPayloadContract;
use christopheraseidl\ModelFiler\Payloads\Contracts\DeleteUploads as DeleteUploadsPayloadContract;
use christopheraseidl\ModelFiler\Payloads\Contracts\MoveUploads as MoveUploadsPayloadContract;
use christopheraseidl\ModelFiler\Services\FileService;
use christopheraseidl\ModelFiler\Tests\TestCase;
use christopheraseidl\ModelFiler\Tests\TestClasses\Payload\TestPayload;
use christopheraseidl\ModelFiler\Tests\TestClasses\Payload\TestPayloadNoConstructor;
use christopheraseidl\ModelFiler\Tests\TestClasses\TestJob;
use christopheraseidl\ModelFiler\Tests\TestModels\TestModel;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

uses(TestCase::class)->in(__DIR__);

// General setup
uses()->beforeEach(function () {
    config()->set('logging.channels.single.path', __DIR__.'/../testing.log');

    $this->model = new TestModel;

    $this->model->save();

    $this->disk = config()->get('model-filer.disk', 'public');

    Storage::fake($this->disk);
    Mail::fake();
})->in('*');

// Events
uses()->beforeEach(function () {
    config()->set('model-filer.broadcast_channel', 'test_channel');
    $this->payload = new TestPayloadNoConstructor;
})->in('Events');

// Handlers/ModelCreationHandler
uses()->beforeEach(function () {
    Bus::fake();

    $this->stringFillableName = 'my-image.png';
    $this->arrayFillableName = 'important-document.pdf';
})->in('Handlers/ModelCreationHandler');

// Handlers/ModelDeletionHandler
uses()->beforeEach(function () {
    $this->payload = app()->makeWith(DeleteUploadDirectoryPayloadContract::class, [
        'modelClass' => $this->model::class,
        'id' => $this->model->id,
        'disk' => $this->disk,
        'path' => $this->model->getUploadPath(),
    ]);

    $this->job = app()->makeWith(DeleteUploadDirectoryJobContract::class, [
        'payload' => $this->payload,
    ]);
})->in('Handlers/ModelDeletionHandler');

// Handlers/ModelUpdateHandler, Handlers/Services/ModelFileChangeTracker
uses()->beforeEach(function () {
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
})->in('Handlers/ModelUpdateHandler', 'Handlers/Services/ModelFileChangeTracker');

// Handlers/Services/BatchManager
uses()->beforeEach(function () {
    Bus::fake();
    Event::fake();

    $this->batch = $this->mock(Batch::class);

    $this->batchManager = new BatchManager;

    $this->model = $this->mock(Model::class, function (MockInterface $mock) {
        $mock->shouldReceive('getAttribute')->with('id')->andReturn(1);
    });

    $this->disk = 'local';

    $this->error = new \Exception('Test error message.');
})->in('Handlers/Services/BatchManager');

// Jobs/CleanOrphanedUploads
uses()->beforeEach(function () {
    $this->path = '/uploads';

    // Create and bind the deleter mock FIRST
    $this->deleter = $this->mock(FileDeleterContract::class);

    $this->payload = $this->partialMock(CleanOrphanedUploadsPayload::class, function (MockInterface $mock) {
        $mock->shouldReceive('getDisk')->andReturn($this->disk);
        $mock->shouldReceive('getPath')->andReturn('uploads');
        $mock->shouldReceive('getCleanupThresholdHours')->andReturn(24);
    });

    $this->cleaner = new CleanOrphanedUploads($this->payload);

    $this->cleaner = $this->partialMock(CleanOrphanedUploads::class, function (MockInterface $mock) {
        $mock->shouldReceive('getPayload')
            ->andReturn($this->payload);
        $mock->shouldReceive('getDeleter')
            ->andReturn($this->deleter);
    });
})->in('Jobs/CleanOrphanedUploads');

// Jobs/DeleteUploadDirectory
uses()->beforeEach(function () {
    $this->path = 'test_models/1';
    $this->payload = $this->mock(DeleteUploadDirectoryPayloadContract::class, function (MockInterface $mock) {
        $mock->shouldReceive('getDisk')->andReturn($this->disk);
        $mock->shouldReceive('getPath')->andReturn($this->path);
    });

    $this->deleter = $this->partialMock(DeleteUploadDirectory::class, function (MockInterface $mock) {
        $mock->shouldReceive('getPayload')->andReturn($this->payload);
    });
})->in('Jobs/DeleteUploadDirectory');

// Jobs/DeleteUploads
uses()->beforeEach(function () {
    $this->path = 'test_models/1';
    $this->payload = $this->mock(DeleteUploadsPayloadContract::class, function (MockInterface $mock) {
        $mock->shouldReceive('getDisk')->andReturn($this->disk);
        $mock->shouldReceive('getPath')->andReturn($this->path);
    });

    $this->deleter = $this->partialMock(DeleteUploads::class, function (MockInterface $mock) {
        $mock->shouldReceive('getPayload')->andReturn($this->payload);
    });
})->in('Jobs/DeleteUploads');

// Jobs/MoveUploads
uses()->beforeEach(function () {
    $this->payload = $this->mock(MoveUploadsPayloadContract::class);

    $this->mover = $this->partialMock(MoveUploads::class, function (MockInterface $mock) {
        $mock->shouldReceive('getPayload')->andReturn($this->payload);
    });
})->in('Jobs/MoveUploads');

// Jobs/Job
uses()->beforeEach(function () {
    $this->payload = new TestPayloadNoConstructor;

    $this->job = new TestJob($this->payload);
})->in('Jobs/Job');

// Jobs/Services/Builder
uses()->beforeEach(function () {
    $this->validator = $this->mock(BuilderValidatorContract::class);

    $this->builder = $this->partialMock(Builder::class, function (MockInterface $mock) {
        $mock->shouldReceive('getValidator')->andReturn($this->validator);
    });

    $this->jobClass = TestJob::class;
    $this->payloadClass = TestPayload::class;
    $this->properties = [
        'property1' => 'value1',
        'property2' => 'value2',
        'property3' => 123,
    ];
})->in('Jobs/Services/Builder');

// Jobs/Services/CircuitBreaker
uses()->beforeEach(function () {
    config(['cache.default' => 'array']);
    Cache::flush();

    $this->breaker = \Mockery::mock(CircuitBreaker::class, [
        'test-circuit',   // Name
        2,                // Failure threshold
        10,               // Recovery timeout in seconds
        3,                // Half-open max attempts
        1,                // Cache TTL
        false,            // Emails enabled
        'admin@mail.com', // Amdin email
    ])->makePartial();

    Carbon::setTestNow(now());
})->in('Jobs/Services/CircuitBreaker');

// Job/Services/FileDeleter
uses()->beforeEach(function () {
    $this->breaker = $this->mock(CircuitBreakerContract::class);

    $this->deleter = $this->partialMock(FileDeleter::class);
    $this->deleter->shouldReceive('getBreaker')
        ->andReturn($this->breaker);

    $name = 'test.txt';
    $this->path = "uploads/{$name}";

    $this->maxAttempts = 3;
})->in('Jobs/Services/FileDeleter');

// Job/Services/FileMover
uses()->beforeEach(function () {
    $this->breaker = $this->mock(CircuitBreakerContract::class);

    $this->mover = $this->partialMock(FileMover::class);
    $this->mover->shouldReceive('getBreaker')
        ->andReturn($this->breaker);

    $name = 'test.txt';
    $this->oldPath = "uploads/{$name}";
    $this->newDir = 'new/dir';
    $this->newPath = "{$this->newDir}/{$name}";
})->in('Jobs/Services/FileMover');

// Jobs/Validators/BuilderValidator
uses()->beforeEach(function () {
    $this->validator = new BuilderValidator;
})->in('Jobs/Validators/BuilderValidator');

// Services/FileService
uses()->beforeEach(function () {
    $this->service = new FileService;
    $this->reflection = new \ReflectionClass($this->service);
})->in('Services/FileService');
