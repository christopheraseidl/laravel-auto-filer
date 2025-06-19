<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\BaseModelEventHandler;

use christopheraseidl\HasUploads\Handlers\BaseModelEventHandler;
use christopheraseidl\HasUploads\Tests\TestTraits\BaseModelEventHandlerHelpers;
use christopheraseidl\Reflect\Reflect;

uses(
    BaseModelEventHandlerHelpers::class
);

/**
 * Tests BaseModelEventHandler class extensibility.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\BaseModelEventHandler
 */
beforeEach(function () {
    $this->setHandler();
});

it('can be extended with concrete implementations', function () {
    expect($this->handler)->toBeInstanceOf(BaseModelEventHandler::class);
});

it('sets disk property correctly from upload service', function () {
    $disk = Reflect::on($this->handler)->disk;

    expect($disk)->toBe($this->diskTestValue);
});

it('implements abstract createJobsFromAttribute method correctly', function () {
    $jobs = Reflect::on($this->handler)->createJobsFromAttribute($this->model, 'string', 'images');

    expect($jobs)->toBe(['job1', 'job2']);
});

it('implements abstract getBatchDescription method correctly', function () {
    $description = Reflect::on($this->handler)->getBatchDescription();

    expect($description)->toBe('Test description');
});

it('can access parent method functionality', function () {
    $jobs = Reflect::on($this->handler)->getAllJobs($this->model);

    expect($jobs)->toBe(['job1', 'job2']);
});
