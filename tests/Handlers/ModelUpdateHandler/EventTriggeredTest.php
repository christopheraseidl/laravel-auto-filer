<?php

namespace christopheraseidl\HasUploads\Tests\Handler\ModelUpdateHandler;

use christopheraseidl\HasUploads\Tests\Traits\AssertsCorrectJobAttributesAndTypesConfigured;
use christopheraseidl\HasUploads\Tests\Traits\ModelUpdateHandlerAssertions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

uses(
    DatabaseTransactions::class,
    ModelUpdateHandlerAssertions::class,
    AssertsCorrectJobAttributesAndTypesConfigured::class
);

/**
 * Tests ModelUpdateHandler behavior triggered by the update event.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\ModelUpdateHandler
 */
beforeEach(function () {
    $this->model->update([
        'string' => $this->newString,
        'array' => $this->newArray,
    ]);
});

it('dispatches the jobs with the correct batch name', function () {
    Bus::assertBatched(function ($batch) {
        return $batch->name === 'Handle uploads for modal update.';
    });
});

it('dispatches the correct move and delete jobs on update', function () {
    $this->assertJobsBatched(2, 2);
});

it('configures jobs with the correct model attributes and types on update', function () {
    $this->assertCorrectJobAttributesAndTypesConfigured(2, 2);
});
