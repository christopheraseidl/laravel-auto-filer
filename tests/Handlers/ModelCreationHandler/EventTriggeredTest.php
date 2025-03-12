<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\ModelCreationHandler;

use christopheraseidl\HasUploads\Jobs\Contracts\MoveUploads;
use christopheraseidl\HasUploads\Tests\TestModels\TestModel;
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
