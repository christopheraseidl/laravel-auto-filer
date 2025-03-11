<?php

namespace christopheraseidl\HasUploads\Tests\Handlers;

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager;
use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Handlers\ModelCreationHandler;
use christopheraseidl\HasUploads\Jobs\Contracts\Builder;
use christopheraseidl\HasUploads\Jobs\MoveUploads;
use christopheraseidl\HasUploads\Tests\TestModels\TestModel;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Bus::fake();

    $this->stringFillableName = 'my-image.png';
    $this->arrayFillableName = 'important-document.pdf';

    $this->model = TestModel::factory()
        ->withStringFillable($this->stringFillableName)
        ->withArrayFillable([$this->arrayFillableName])
        ->create();

    $this->handler = Reflect::on(new ModelCreationHandler(
        app(UploadService::class),
        app(Builder::class),
        app(BatchManager::class),
        app(ModelFileChangeTracker::class)
    ));
});

it('dispatches the correct move upload jobs when handle() is called', function () {
    $this->handler->handle($this->model);

    Bus::assertBatched(function ($batch) {
        return $batch->jobs->count() === 2
        && $batch->jobs->filter(fn ($job) => $job instanceof MoveUploads)->count() === 2;
    });
});

it('configures the move upload jobs with correct attributes and types when handle() is called', function () {
    $this->handler->handle($this->model);

    Bus::assertBatched(function ($batch) {
        $attributes = $batch->jobs->map(
            fn ($job) => $job->getPayload()->getModelAttribute()
        );

        $types = $batch->jobs->map(
            fn ($job) => $job->getPayload()->getModelAttributeType()
        );

        return $attributes->contains('string')
            && $attributes->contains('array')
            && $types->contains('images')
            && $types->contains('documents')
            && $attributes->filter(fn ($attr) => $attr === 'string')->count() === 1
            && $attributes->filter(fn ($attr) => $attr === 'array')->count() === 1
            && $types->filter(fn ($type) => $type === 'images')->count() === 1
            && $types->filter(fn ($type) => $type === 'documents')->count() === 1;
    });
});

it('gets all jobs for a model with uploadable attributes', function () {
    $jobs = $this->handler->getAllJobs($this->model);

    expect($jobs)->toHaveCount(2)
        ->and($jobs[0])->toBeInstanceOf(MoveUploads::class)
        ->and($jobs[1])->toBeInstanceOf(MoveUploads::class);
});

it('creates jobs from attribute correctly', function () {
    $stringJob = $this->handler->createJobsFromAttribute($this->model, 'string', 'images');
    $arrayJob = $this->handler->createJobsFromAttribute($this->model, 'array', 'documents');

    expect($stringJob)->toHaveCount(1)
        ->and($stringJob[0])->toBeInstanceOf(MoveUploads::class)
        ->and($stringJob[0]->getPayload()->getModelAttribute())->toBe('string')
        ->and($stringJob[0]->getPayload()->getModelAttributeType())->toBe('images')
        ->and($arrayJob)->toHaveCount(1)
        ->and($arrayJob[0])->toBeInstanceOf(MoveUploads::class)
        ->and($arrayJob[0]->getPayload()->getModelAttribute())->toBe('array')
        ->and($arrayJob[0]->getPayload()->getModelAttributeType())->toBe('documents');
});

it('returns null when creating jobs for null attribute', function () {
    $model = TestModel::factory()->create(['string' => null]);

    $jobs = $this->handler->createJobsFromAttribute($model, 'string', 'images');

    expect($jobs)->toBeNull();
});

it('returns the correct batch description', function () {
    expect($this->handler->getBatchDescription())->toBe('Handle uploads for model creation.');
});
