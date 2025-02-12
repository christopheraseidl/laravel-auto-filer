<?php

namespace christopheraseidl\HasUploads\Tests\Handlers;

use christopheraseidl\HasUploads\Jobs\DeleteUploadDirectory;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Bus::fake();

    $this->id = $this->model->id;

    $this->dir = $this->model->getUploadPath();

    $this->model->delete();
});

it('dispatches the correct delete upload directory job on model deletion', function () {
    Bus::assertDispatched(DeleteUploadDirectory::class, function ($job) {
        return Reflect::on($job)->id === $this->id
            && Reflect::on($job)->dir === $this->dir;
    });
});
