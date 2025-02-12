<?php

namespace christopheraseidl\HasUploads\Tests\Handlers;

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
});

it('dispatches the correct move upload jobs on model creation', function () {
    Bus::assertBatched(function ($batch) {
        return $batch->name === 'Handle uploads for model creation'
            && $batch->jobs->count() === 2
            && $batch->jobs[0] instanceof MoveUploads
            && $batch->jobs[1] instanceof MoveUploads;
    });
});

it('configures the move upload jobs with correct attributes', function () {
    Bus::assertBatched(function ($batch) {
        $jobs = $batch->jobs;

        return Reflect::on($jobs[0])->attribute === 'string'
            && Reflect::on($jobs[0])->type === 'images'
            && Reflect::on($jobs[1])->attribute === 'array'
            && Reflect::on($jobs[1])->type === 'documents';
    });
});
