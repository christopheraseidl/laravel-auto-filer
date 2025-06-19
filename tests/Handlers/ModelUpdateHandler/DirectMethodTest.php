<?php

namespace christopheraseidl\ModelFiler\Tests\Handlers\ModelUpdateHandler;

use christopheraseidl\ModelFiler\Jobs\Contracts\DeleteUploads;
use christopheraseidl\ModelFiler\Jobs\Contracts\MoveUploads;
use christopheraseidl\ModelFiler\Tests\TestTraits\AssertsCorrectJobAttributesAndTypesConfigured;
use christopheraseidl\ModelFiler\Tests\TestTraits\ModelUpdateHandlerHelpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(
    DatabaseTransactions::class,
    ModelUpdateHandlerHelpers::class,
    AssertsCorrectJobAttributesAndTypesConfigured::class
);

/**
 * Tests ModelUpdateHandler behavior triggered directly by methods.
 *
 * @covers \christopheraseidl\ModelFiler\Handlers\ModelUpdateHandler
 */
beforeEach(function () {
    $this->model->fill([
        'string' => $this->newString,
        'array' => $this->newArray,
    ]);

    $this->setHandler();
});

it('dispatches the correct move and delete jobs when handle() is called', function () {
    $this->handler->handle($this->model);

    $this->assertJobsBatched(2, 2);
});

it('configures jobs with the correct model attributes and types when handle() is called', function () {
    $this->handler->handle($this->model);

    $this->assertCorrectJobAttributesAndTypesConfigured(2, 2);
});

it('gets all jobs for a model with uploadable attributes', function () {
    $jobs = $this->handler->getAllJobs($this->model);

    expect($jobs)->toHaveCount(4);
    expect(array_filter($jobs, fn ($job) => $job instanceof DeleteUploads))->toHaveCount(2);
    expect(array_filter($jobs, fn ($job) => $job instanceof MoveUploads))->toHaveCount(2);
});

it('creates jobs from attribute correctly', function (string $attribute, string $type) {
    $jobs = $this->handler->createJobsFromAttribute($this->model, $attribute, $type);

    $deleteJobs = array_filter($jobs, fn ($job) => $job instanceof DeleteUploads);

    $moveJobs = array_filter($jobs, fn ($job) => $job instanceof MoveUploads);

    $jobAttributes = array_map(function ($job) {
        return $job->getPayload()->getModelAttribute();
    }, $jobs);

    $jobTypes = array_map(function ($job) {
        return $job->getPayload()->getModelAttributeType();
    }, $jobs);

    expect($jobs)->toHaveCount(2);
    expect($deleteJobs)->toHaveCount(1);
    expect($moveJobs)->toHaveCount(1);
    expect($jobAttributes)->toHaveCount(2)
        ->and($jobAttributes)->toContain($attribute);
    expect(array_unique($jobAttributes))->toHaveCount(1);
    expect($jobTypes)->toHaveCount(2)
        ->and($jobTypes)->toContain($type);
    expect(array_unique($jobTypes))->toHaveCount(1);
})->with([
    ['string', 'images'],
    ['array', 'documents'],
]);

it('returns the correct batch description', function () {
    expect($this->handler->getBatchDescription())->toBe('Handle uploads for model update.');
});
