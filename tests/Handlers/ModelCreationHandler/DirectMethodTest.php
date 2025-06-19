<?php

namespace christopheraseidl\ModelFiler\Tests\Handlers\ModelCreationHandler;

use christopheraseidl\ModelFiler\Jobs\Contracts\MoveUploads;
use christopheraseidl\ModelFiler\Tests\TestModels\TestModel;
use christopheraseidl\ModelFiler\Tests\TestTraits\AssertsCorrectJobAttributesAndTypesConfigured;
use christopheraseidl\ModelFiler\Tests\TestTraits\ModelCreationHandlerAssertions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

uses(
    DatabaseTransactions::class,
    ModelCreationHandlerAssertions::class,
    AssertsCorrectJobAttributesAndTypesConfigured::class
);

/**
 * Tests ModelCreationHandler behavior triggered directly by methods.
 *
 * @covers \christopheraseidl\ModelFiler\Handlers\ModelCreationHandler
 */
beforeEach(function () {
    $this->model = TestModel::factory()
        ->withStringFillable($this->stringFillableName)
        ->withArrayFillable([$this->arrayFillableName])
        ->createQuietly();

    $this->setHandler();
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

    $this->assertCorrectJobAttributesAndTypesConfigured(1, 1);
});

it('gets all jobs for a model with uploadable attributes', function () {
    $jobs = $this->handler->getAllJobs($this->model);

    expect($jobs)->toHaveCount(2);
    expect(array_filter($jobs, fn ($job) => $job instanceof MoveUploads))->toHaveCount(2);
});

it('creates jobs from attribute correctly', function (string $attribute, string $type) {
    $jobs = $this->handler->createJobsFromAttribute($this->model, $attribute, $type);

    $moveJobs = array_filter($jobs, fn ($job) => $job instanceof MoveUploads);

    $jobAttributes = array_map(function ($job) {
        return $job->getPayload()->getModelAttribute();
    }, $jobs);

    $jobTypes = array_map(function ($job) {
        return $job->getPayload()->getModelAttributeType();
    }, $jobs);

    expect($jobs)->toHaveCount(1);
    expect($moveJobs)->toHaveCount(1);
    expect($jobAttributes)->toHaveCount(1)
        ->and($jobAttributes)->toContain($attribute);
    expect($jobTypes)->toHaveCount(1)
        ->and($jobTypes)->toContain($type);
})->with([
    ['string', 'images'],
    ['array', 'documents'],
]);

it('returns null when creating jobs for null attribute', function () {
    $model = TestModel::factory()->create(['string' => null]);

    $jobs = $this->handler->createJobsFromAttribute($model, 'string', 'images');

    expect($jobs)->toBeNull();
});

it('returns the correct batch description', function () {
    expect($this->handler->getBatchDescription())->toBe('Handle uploads for model creation.');
});
