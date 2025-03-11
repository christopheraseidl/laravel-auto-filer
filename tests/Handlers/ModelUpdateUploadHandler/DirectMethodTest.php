<?php

namespace christopheraseidl\HasUploads\Tests\Handlers;

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Handlers\Contracts\BatchManager;
use christopheraseidl\HasUploads\Handlers\Contracts\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Handlers\ModelUpdateHandler;
use christopheraseidl\HasUploads\Jobs\Contracts\Builder;
use christopheraseidl\HasUploads\Jobs\DeleteUploads;
use christopheraseidl\HasUploads\Jobs\MoveUploads;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Bus::fake();

    $string = 'image.jpg';
    Storage::disk($this->disk)->put($string, 100);

    $array = ['document1.doc', 'document2.md'];
    Storage::disk($this->disk)->put($array[0], 200);
    Storage::disk($this->disk)->put($array[1], 200);

    $this->model->string = $string;
    $this->model->array = $array;
    $this->model->saveQuietly();

    $string = 'new-image.png';
    Storage::disk($this->disk)->put($string, 100);

    $array = $this->model->array;
    $newArrayItem = 'new-doc.txt';
    Storage::disk($this->disk)->put($newArrayItem, 200);

    $this->unchangedModel = $this->model->replicate();
    $this->unchangedModel->saveQuietly();

    $this->model->fill([
        'string' => $string,
        'array' => [$array[1], $newArrayItem],
    ]);

    $this->handler = Reflect::on(new ModelUpdateHandler(
        app(UploadService::class),
        app(Builder::class),
        app(BatchManager::class),
        app(ModelFileChangeTracker::class)
    ));
});

it('dispatches the correct move and delete jobs when handle() is called', function () {
    $this->handler->handle($this->model);

    Bus::assertBatched(function ($batch) {
        return count($batch->jobs) === 4
            && $batch->jobs->filter(fn ($job) => $job instanceof DeleteUploads)->count() === 2
            && $batch->jobs->filter(fn ($job) => $job instanceof MoveUploads)->count() === 2;
    });
});

it('configures jobs with the correct model attributes and types when handle() is called', function () {
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
            && $attributes->filter(fn ($attr) => $attr === 'string')->count() === 2
            && $attributes->filter(fn ($attr) => $attr === 'array')->count() === 2
            && $types->filter(fn ($type) => $type === 'images')->count() === 2
            && $types->filter(fn ($type) => $type === 'documents')->count() === 2;
    });
});

it('gets all jobs for a model with uploadable attributes', function () {
    $jobs = $this->handler->getAllJobs($this->model);

    expect($jobs)->toHaveCount(4)
        ->and(array_filter($jobs, fn ($job) => $job instanceof DeleteUploads))->toHaveCount(2)
        ->and(array_filter($jobs, fn ($job) => $job instanceof MoveUploads))->toHaveCount(2);
});

it('creates jobs from attribute correctly', function () {
    $stringJobs = $this->handler->createJobsFromAttribute($this->model, 'string', 'images');
    $arrayJobs = $this->handler->createJobsFromAttribute($this->model, 'array', 'documents');

    expect($stringJobs)->toHaveCount(2)
        ->and(array_filter($stringJobs, fn ($job) => $job instanceof DeleteUploads))->toHaveCount(1)
        ->and(array_filter($stringJobs, fn ($job) => $job instanceof MoveUploads))->toHaveCount(1)
        ->and($stringJobs[0]->getPayload()->getModelAttribute())->toBe('string')
        ->and($stringJobs[0]->getPayload()->getModelAttributeType())->toBe('images')
        ->and($stringJobs[1]->getPayload()->getModelAttribute())->toBe('string')
        ->and($stringJobs[1]->getPayload()->getModelAttributeType())->toBe('images')
        ->and(array_filter($arrayJobs, fn ($job) => $job instanceof DeleteUploads))->toHaveCount(1)
        ->and(array_filter($arrayJobs, fn ($job) => $job instanceof MoveUploads))->toHaveCount(1)
        ->and($arrayJobs[0]->getPayload()->getModelAttribute())->toBe('array')
        ->and($arrayJobs[0]->getPayload()->getModelAttributeType())->toBe('documents')
        ->and($arrayJobs[1]->getPayload()->getModelAttribute())->toBe('array')
        ->and($arrayJobs[1]->getPayload()->getModelAttributeType())->toBe('documents');
});

it('returns the correct batch description', function () {
    expect($this->handler->getBatchDescription())->toBe('Handle uploads for modal update.');
});
