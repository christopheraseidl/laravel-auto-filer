<?php

namespace christopheraseidl\ModelFiler\Tests\ModelFiler;

use christopheraseidl\ModelFiler\Handlers\ModelCreationHandler;
use christopheraseidl\ModelFiler\Handlers\ModelDeletionHandler;
use christopheraseidl\ModelFiler\Handlers\ModelUpdateHandler;
use christopheraseidl\ModelFiler\Services\Contracts\FileService;
use christopheraseidl\ModelFiler\Tests\TestModels\TestModel;
use christopheraseidl\Reflect\Reflect;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;

uses(DatabaseTransactions::class);

it('calls the handlers on model events and sets the fileService static property', function () {
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

it('sets the fileService static property', function () {
    expect(Reflect::on($this->model)->fileService)->toBeInstanceOf(FileService::class);
});
