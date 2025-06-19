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
 * Tests ModelCreationHandler behavior triggered by the creation event.
 *
 * @covers \christopheraseidl\ModelFiler\Handlers\ModelCreationHandler
 */
beforeEach(function () {
    $this->model = TestModel::factory()
        ->withStringFillable($this->stringFillableName)
        ->withArrayFillable([$this->arrayFillableName])
        ->create();
});

it('dispatches the jobs with the correct batch name', function () {
    Bus::assertBatched(function ($batch) {
        return $batch->name === 'Handle uploads for model creation.';
    });
});

it('dispatches the correct move upload jobs on model creation', function () {
    Bus::assertBatched(function ($batch) {
        return $batch->jobs->count() === 2
        && $batch->jobs->filter(fn ($job) => $job instanceof MoveUploads)->count() === 2;
    });
});

it('configures the move upload jobs with correct attributes and types on model creation', function () {
    $this->assertCorrectJobAttributesAndTypesConfigured(1, 1);
});
