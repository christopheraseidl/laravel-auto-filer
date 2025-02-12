<?php

namespace christopheraseidl\HasUploads\Tests\Handlers;

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
});

it('dispatches the correct move and delete jobs on update', function () {
    $string = 'new-image.png';
    Storage::disk($this->disk)->put($string, 100);

    $array = $this->model->array;
    $newArrayItem = 'new-doc.txt';
    Storage::disk($this->disk)->put($newArrayItem, 200);

    $this->model->update([
        'string' => $string,
        'array' => [$array[1], $newArrayItem],
    ]);

    Bus::assertBatched(function ($batch) {
        $jobs = $batch->jobs;

        return count($jobs) === 4
            && $jobs[0] instanceof DeleteUploads
            && Reflect::on($jobs[0])->path === 'image.jpg'
            && Reflect::on($jobs[0])->files === ['image.jpg']
            && $jobs[1] instanceof MoveUploads
            && Reflect::on($jobs[1])->attribute === 'string'
            && Reflect::on($jobs[1])->type === 'images'
            && $jobs[2] instanceof DeleteUploads
            && Reflect::on($jobs[2])->path === 'document1.doc'
            && Reflect::on($jobs[2])->files === ['document1.doc']
            && $jobs[3] instanceof MoveUploads
            && Reflect::on($jobs[3])->attribute === 'array'
            && Reflect::on($jobs[3])->type === 'documents';
    });
});
