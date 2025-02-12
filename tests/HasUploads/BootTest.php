<?php

namespace christopheraseidl\HasUploads\Tests\HasUploads;

use christopheraseidl\HasUploads\Handlers\HandleUploadsOnModelCreation;
use christopheraseidl\HasUploads\Handlers\HandleUploadsOnModelDeletion;
use christopheraseidl\HasUploads\Handlers\HandleUploadsOnModelUpdate;
use christopheraseidl\HasUploads\Tests\TestModels\TestModel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;

uses(DatabaseTransactions::class);

it('calls the handlers on model events', function () {
    $this->mock(HandleUploadsOnModelCreation::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once();
    });

    $this->mock(HandleUploadsOnModelDeletion::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once();
    });

    $this->mock(HandleUploadsOnModelUpdate::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once();
    });

    $model = TestModel::factory()->create();
    $model->delete();
});
