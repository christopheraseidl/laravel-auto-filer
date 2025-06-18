<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\ModelUpdateHandler;

use christopheraseidl\HasUploads\Tests\TestTraits\ModelUpdateHandlerHelpers;

uses(ModelUpdateHandlerHelpers::class);

/**
 * Tests ModelUpdateHandler createJobsFromAttribute() method with an empty
 * attribute (whether string or array).
 *
 * @covers \christopheraseidl\HasUploads\Handlers\ModelUpdateHandler
 */
beforeEach(function () {
    $this->setHandler();
});

it('returns an empty array when creating jobs for unchanged attributes', function () {
    $stringJobs = $this->handler->createJobsFromAttribute($this->model, 'string', 'images');
    $arrayJobs = $this->handler->createJobsFromAttribute($this->model, 'array', 'documents');

    expect($stringJobs)->toBeEmpty()
        ->and($arrayJobs)->toBeEmpty();
});
