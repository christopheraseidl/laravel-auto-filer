<?php

namespace christopheraseidl\HasUploads\Tests\Handlers;

use christopheraseidl\HasUploads\Contracts\BatchHandler;
use christopheraseidl\HasUploads\Contracts\JobFactory;
use christopheraseidl\HasUploads\Contracts\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Handlers\HandleUploadsOnModelCreation;
use christopheraseidl\HasUploads\Jobs\MoveUploads;
use christopheraseidl\HasUploads\Tests\TestModels\TestModel;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->stringFillableName = 'my-image.png';
    $this->arrayFillableName = 'important-document.pdf';

    $this->model = TestModel::factory()
        ->withStringFillable($this->stringFillableName)
        ->withArrayFillable([$this->arrayFillableName])
        ->create();

    $this->handler = Reflect::on(new HandleUploadsOnModelCreation(
        app(UploadService::class),
        app(JobFactory::class),
        app(BatchHandler::class),
        app(ModelFileChangeTracker::class)
    ));
});

it('gets all jobs for a model with uploadable attributes', function () {
    $jobs = $this->handler->getAllJobs($this->model);

    expect($jobs)->toHaveCount(2)
        ->and($jobs[0])->toBeInstanceOf(MoveUploads::class)
        ->and($jobs[1])->toBeInstanceOf(MoveUploads::class);
});

it('creates jobs from attribute correctly', function () {
    $jobs = $this->handler->createJobsFromAttribute($this->model, 'string', 'images');

    expect($jobs)->toHaveCount(1)
        ->and($jobs[0])->toBeInstanceOf(MoveUploads::class)
        ->and(Reflect::on($jobs[0])->payload->getModelAttribute())->toBe('string')
        ->and(Reflect::on($jobs[0])->payload->getModelAttributeType())->toBe('images');
});

it('returns null when creating jobs for null attribute', function () {
    $model = TestModel::factory()->create(['string' => null]);

    $jobs = $this->handler->createJobsFromAttribute($model, 'string', 'images');

    expect($jobs)->toBeNull();
});

it('returns correct batch description', function () {
    expect($this->handler->getBatchDescription())->toBe('Handle uploads for model creation.');
});
