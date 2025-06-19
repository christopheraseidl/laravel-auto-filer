<?php

namespace christopheraseidl\ModelFiler\Tests\Handlers\ModelUpdateHandler;

use christopheraseidl\ModelFiler\Tests\TestTraits\ModelUpdateHandlerHelpers;

uses(ModelUpdateHandlerHelpers::class);

/**
 * Tests ModelUpdateHandler createJobsFromAttribute() method with an empty
 * attribute (whether string or array).
 *
 * @covers \christopheraseidl\ModelFiler\Handlers\ModelUpdateHandler
 */
beforeEach(function () {
    $this->setHandler();
});

it('returns an empty array when creating jobs for unchanged attributes', function () {
    $stringJobs = $this->handler->createJobsFromAttribute($this->model, 'string', 'images');
    $arrayJobs = $this->handler->createJobsFromAttribute($this->model, 'array', 'documents');

    expect($stringJobs)->toBeEmpty();
    expect($arrayJobs)->toBeEmpty();
});
