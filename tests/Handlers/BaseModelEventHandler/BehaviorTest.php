<?php

namespace christopheraseidl\HasUploads\Tests\Handlers\BaseModelEventHandler;

use christopheraseidl\HasUploads\Handlers\Services\BatchManager;
use christopheraseidl\HasUploads\Handlers\Services\ModelFileChangeTracker;
use christopheraseidl\HasUploads\Jobs\Services\Builder;
use christopheraseidl\HasUploads\Services\UploadService;
use christopheraseidl\HasUploads\Tests\TestClasses\BaseModelEventHandlerTestClass;
use christopheraseidl\HasUploads\Tests\TestTraits\BaseModelEventHandlerAssertions;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;

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

    $uploadService = $this->mock(UploadService::class, function (MockInterface $mock) {
        $mock->shouldReceive('getDisk')->andReturn($this->diskTestValue);
    });

    $builder = $this->mock(Builder::class);
    $this->batchManager = $this->mock(BatchManager::class);
    $fileTracker = $this->mock(ModelFileChangeTracker::class);

    $this->handler = \Mockery::mock(BaseModelEventHandlerTestClass::class, [
        $uploadService, $builder, $this->batchManager, $fileTracker,
    ])->makePartial();

    $this->handler->shouldAllowMockingProtectedMethods();

    $this->jobs = [
        $this->mock('Job1'),
        $this->mock('Job2'),
    ];

    $this->model = $this->partialMock($this->model::class);

    $this->model->shouldReceive('getUploadableAttributes')
        ->andReturn([
            'image' => 'image',
            'document' => 'document',
        ]);
});

it('dispatches batch with jobs when handle is called', function () {
    $this->handler->shouldReceive('getAllJobs')->once()->andReturn($this->jobs);
    $this->handler->shouldReceive('getBatchDescription')->once()->andReturn('Test description');
    $this->batchManager->shouldReceive('dispatch')
        ->once()
        ->with($this->jobs, $this->model, $this->diskTestValue, 'Test description');

    $this->handler->handle($this->model);
});

it('returns all jobs when no filter is applied', function () {
    $this->handler->shouldReceive('createJobsFromAttribute')
        ->with($this->model, 'image', 'image')
        ->andReturn([$this->jobs[0]]);

    $this->handler->shouldReceive('createJobsFromAttribute')
        ->with($this->model, 'document', 'document')
        ->andReturn([$this->jobs[1]]);

    $jobs = $this->handler->getAllJobs($this->model);

    expect($jobs)->toHaveCount(2)
        ->and($jobs)->toEqual($this->jobs);
});

it('filters jobs based on provided criteria', function (array $expectedJobIndexes, ?\Closure $filter = null) {
    $this->handler->shouldReceive('createJobsFromAttribute')
        ->with($this->model, 'image', 'image')
        ->andReturn([$this->jobs[0]]);

    $this->handler->shouldReceive('createJobsFromAttribute')
        ->with($this->model, 'document', 'document')
        ->andReturn([$this->jobs[1]]);

    $jobs = $this->handler->getAllJobs($this->model, $filter);
    $expectedJobs = array_map(fn ($index) => $this->jobs[$index], $expectedJobIndexes);

    expect($jobs)->toEqual($expectedJobs);
})->with([
    'no filter' => [
        [0, 1],
        null,
    ],
    'filter images' => [
        [0],
        fn ($model, $attribute) => $attribute === 'image',
    ],
]);
