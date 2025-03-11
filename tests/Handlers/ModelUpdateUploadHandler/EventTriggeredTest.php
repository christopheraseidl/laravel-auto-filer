<?php

namespace christopheraseidl\HasUploads\Tests\Handlers;

use christopheraseidl\HasUploads\Jobs\DeleteUploads;
use christopheraseidl\HasUploads\Jobs\MoveUploads;
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

    $this->model->update([
        'string' => $string,
        'array' => [$array[1], $newArrayItem],
    ]);
});

it('dispatches the jobs with the correct batch name', function () {
    Bus::assertBatched(function ($batch) {
        return $batch->name === 'Handle uploads for modal update.';
    });
});

it('dispatches the correct move and delete jobs on update', function () {
    Bus::assertBatched(function ($batch) {
        return count($batch->jobs) === 4
            && $batch->jobs->filter(fn ($job) => $job instanceof DeleteUploads)->count() === 2
            && $batch->jobs->filter(fn ($job) => $job instanceof MoveUploads)->count() === 2;
    });
});

it('configures jobs with the correct model attributes and types on update', function () {
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
