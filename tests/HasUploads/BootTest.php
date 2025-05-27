<?php

namespace christopheraseidl\HasUploads\Tests\HasUploads;

use christopheraseidl\HasUploads\Contracts\UploadService;
use christopheraseidl\HasUploads\Handlers\ModelCreationHandler;
use christopheraseidl\HasUploads\Handlers\ModelDeletionHandler;
use christopheraseidl\HasUploads\Handlers\ModelUpdateHandler;
use christopheraseidl\HasUploads\Tests\TestModels\TestModel;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;

uses(DatabaseTransactions::class);

it('calls the handlers on model events and sets the uploadService static property', function () {
    $this->mock(ModelCreationHandler::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once();
    });

    $this->mock(ModelDeletionHandler::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once();
    });

    $this->mock(ModelUpdateHandler::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once();
    });

    $model = TestModel::factory()->create();
    $model->delete();
});

it('sets the uploadService static property', function () {
    expect(Reflect::on($this->model)->uploadService)->toBeInstanceOf(UploadService::class);
});
