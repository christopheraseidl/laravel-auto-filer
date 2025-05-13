<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\BaseModelEventHandler;

use christopheraseidl\HasUploads\Handlers\Services\BatchManager;
use christopheraseidl\HasUploads\Handlers\Services\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Jobs\Services\Builder;
use christopheraseidl\HasUploads\Services\UploadService;
use christopheraseidl\HasUploads\Tests\TestClasses\BaseModelEventHandlerTestClass;
use christopheraseidl\HasUploads\Tests\TestTraits\BaseModelEventHandlerAssertions;
use Illuminate\Support\Facades\Bus;

uses(
    BaseModelEventHandlerAssertions::class
);

/**
 * Tests BaseModelEventHandler class method behavior.
 *
 * @covers \christopheraseidl\HasUploads\Handlers\BaseModelEventHandler
 */
beforeEach(function () {
    Bus::fake();

    $this->setHandler();

    $this->uploadService = \Mockery::mock(UploadService::class);
    $this->uploadService->shouldReceive('getDisk')->andReturn($this->diskTestValue);

    $this->builder = \Mockery::mock(Builder::class);
    $this->fileTracker = \Mockery::mock(ModelFileChangeTracker::class);
    $this->batchManager = \Mockery::mock(BatchManager::class);

    $this->handler = \Mockery::mock(BaseModelEventHandlerTestClass::class, [
        $this->uploadService, $this->builder, $this->batchManager, $this->fileTracker,
    ])->makePartial();

    $this->handler->shouldAllowMockingProtectedMethods();

    $this->jobs = ['job1', 'job2', 'task3'];
});

it('dispatches a batch of jobs for changed attributes when handling a model', function () {
    $this->handler->shouldReceive('getAllJobs')->once()->andReturn($this->jobs);
    $this->handler->shouldReceive('getBatchDescription')->once()->andReturn('Test batch');

    $this->batchManager->shouldReceive('dispatch')
        ->once()
        ->with($this->jobs, $this->model, $this->diskTestValue, 'Test batch');

    $this->handler->handle($this->model);
});

it('returns an array of jobs based on attributes and filter criteria', function () {
    $this->model = \Mockery::mock($this->model)->makePartial();
    $this->model->shouldReceive('getUploadableAttributes')
        ->andReturn([
            'image' => 'image',
            'document' => 'document',
            'video' => 'video',
        ]);

    $this->handler->shouldReceive('createJobsFromAttribute')
        ->with($this->model, 'image', 'image')
        ->andReturn(['job_image_1', 'job_image_2']);

    $this->handler->shouldReceive('createJobsFromAttribute')
        ->with($this->model, 'document', 'document')
        ->andReturn(['job_document']);

    $this->handler->shouldReceive('createJobsFromAttribute')
        ->with($this->model, 'video', 'video')
        ->andReturn(['job_video_1', 'job_video_2', 'job_video_3']);

    // Test unfiltered jobs
    $unfilteredJobs = $this->handler->getAllJobs($this->model);
    expect($unfilteredJobs)->toHaveCount(6)
        ->and($unfilteredJobs)->toContain(
            'job_image_1',
            'job_image_2',
            'job_document',
            'job_video_1',
            'job_video_2',
            'job_video_3'
        );

    // Filter jobs that only contain 'image' attribute
    $imageFilter = fn ($model, $attribute) => $attribute === 'image';
    $imageJobs = $this->handler->getAllJobs($this->model, $imageFilter);
    expect($imageJobs)->toHaveCount(2)
        ->and($imageJobs)->toContain('job_image_1', 'job_image_2')
        ->and($imageJobs)->not->toContain(
            'job_document',
            'job_video_1',
            'job_video_2',
            'job_video_3'
        );
});
